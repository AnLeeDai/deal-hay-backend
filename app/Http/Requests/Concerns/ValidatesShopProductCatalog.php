<?php

namespace App\Http\Requests\Concerns;

use App\Models\ShopProduct;
use App\Models\ShopProductOption;
use App\Models\ShopProductVariant;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

trait ValidatesShopProductCatalog
{
    protected function prepareShopProductCatalogForValidation(): void
    {
        /** @var ShopProduct|null $product */
        $product = $this->route('shopProduct');
        $variants = is_array($this->input('variants')) ? $this->input('variants') : [];
        $optionsWereProvided = $this->exists('options');
        $options = is_array($this->input('options')) ? $this->input('options') : [];

        if (! $optionsWereProvided && $product instanceof ShopProduct) {
            $product->loadMissing(['options.values', 'variants.optionValues.option']);
            $options = $product->options->map(fn (ShopProductOption $option): array => [
                'id' => (string) $option->getKey(),
                'name' => (string) $option->name,
                'values' => $option->values->pluck('value')->map('strval')->all(),
            ])->all();
        }

        if ($options === []) {
            $options = $this->legacyOptions($variants);
        }

        $normalizedOptions = collect($options)
            ->map(function (mixed $option): mixed {
                if (! is_array($option)) {
                    return $option;
                }

                return [
                    ...$option,
                    'id' => filled($option['id'] ?? null) ? trim((string) $option['id']) : null,
                    'name' => Str::squish((string) ($option['name'] ?? '')),
                    'values' => collect((array) ($option['values'] ?? []))
                        ->map(fn (mixed $value): string => Str::squish((string) $value))
                        ->all(),
                ];
            })
            ->values()
            ->all();

        $canonicalOptionNames = collect($normalizedOptions)
            ->filter(fn (mixed $option): bool => is_array($option) && filled($option['name'] ?? null))
            ->mapWithKeys(fn (array $option): array => [
                $this->catalogKey((string) $option['name']) => (string) $option['name'],
            ]);
        $existingVariants = $product instanceof ShopProduct
            ? $product->variants->keyBy(fn (ShopProductVariant $variant): string => (string) $variant->getKey())
            : collect();

        $normalizedVariants = collect($variants)
            ->map(function (mixed $variant, int $position) use ($canonicalOptionNames, $existingVariants): mixed {
                if (! is_array($variant)) {
                    return $variant;
                }

                $existingVariant = filled($variant['id'] ?? null)
                    ? $existingVariants->get((string) $variant['id'])
                    : null;
                $optionValues = is_array($variant['option_values'] ?? null)
                    ? $variant['option_values']
                    : ($existingVariant instanceof ShopProductVariant ? $existingVariant->optionValueMap() : []);
                $normalizedOptionValues = [];

                foreach ($optionValues as $name => $value) {
                    $normalizedName = Str::squish((string) $name);
                    $canonicalName = $canonicalOptionNames->get($this->catalogKey($normalizedName), $normalizedName);
                    $normalizedOptionValues[$canonicalName] = Str::squish((string) $value);
                }

                $this->applyLegacyOptionValue(
                    $normalizedOptionValues,
                    $canonicalOptionNames,
                    ['Màu sắc', 'Mau sac', 'Color'],
                    $variant['color'] ?? null,
                );
                $this->applyLegacyOptionValue(
                    $normalizedOptionValues,
                    $canonicalOptionNames,
                    ['Kích thước', 'Kich thuoc', 'Size'],
                    $variant['size'] ?? null,
                );

                return [
                    ...$variant,
                    'id' => filled($variant['id'] ?? null) ? trim((string) $variant['id']) : null,
                    'color' => filled($variant['color'] ?? null) ? Str::squish((string) $variant['color']) : null,
                    'size' => filled($variant['size'] ?? null) ? Str::squish((string) $variant['size']) : null,
                    'option_values' => $normalizedOptionValues,
                    'price' => $this->normalizeShopProductCurrency($variant['price'] ?? null),
                    'position' => $position,
                    'is_default' => $this->booleanInput($variant['is_default'] ?? false),
                ];
            })
            ->values()
            ->all();

        if ($normalizedVariants !== [] && ! collect($normalizedVariants)->contains(
            fn (mixed $variant): bool => is_array($variant) && ($variant['is_default'] ?? false) === true,
        )) {
            $defaultIndex = collect($normalizedVariants)->search(
                fn (mixed $variant): bool => is_array($variant) && $this->booleanInput($variant['is_active'] ?? false),
            );
            $defaultIndex = $defaultIndex === false ? 0 : $defaultIndex;

            if (is_array($normalizedVariants[$defaultIndex] ?? null)) {
                $normalizedVariants[$defaultIndex]['is_default'] = true;
            }
        }

        $this->merge([
            'price' => $this->normalizeShopProductCurrency($this->input('price')),
            'options' => $normalizedOptions,
            'variants' => $normalizedVariants,
        ]);
    }

    /** @return array<string, mixed> */
    protected function shopProductCatalogRules(?ShopProduct $product = null): array
    {
        return [
            'options' => ['nullable', 'array', 'max:3'],
            'options.*.id' => [
                'nullable',
                'uuid',
                'distinct',
                ...($product instanceof ShopProduct ? [
                    Rule::exists('shop_product_options', 'id')->where('shop_product_id', $product->getKey()),
                ] : []),
            ],
            'options.*.name' => ['required_with:options', 'string', 'max:80'],
            'options.*.values' => ['required_with:options', 'array', 'min:1', 'max:50'],
            'options.*.values.*' => ['required', 'string', 'max:80'],
            'variants' => ['nullable', 'array', 'max:100'],
            'variants.*.id' => [
                'nullable',
                'uuid',
                'distinct',
                ...($product instanceof ShopProduct ? [
                    Rule::exists('shop_product_variants', 'id')
                        ->where('shop_product_id', $product->getKey())
                        ->whereNull('retired_at'),
                ] : []),
            ],
            'variants.*.color' => ['nullable', 'string', 'max:80'],
            'variants.*.size' => ['nullable', 'string', 'max:80'],
            'variants.*.option_values' => ['required_with:variants', 'array', 'max:3'],
            'variants.*.option_values.*' => ['required', 'string', 'max:80'],
            'variants.*.price' => ['nullable', 'integer', 'min:1000', 'max:999999999'],
            'variants.*.stock' => ['required_with:variants', 'integer', 'min:0', 'max:999999'],
            'variants.*.is_active' => ['required_with:variants', 'boolean'],
            'variants.*.is_default' => ['required_with:variants', 'boolean'],
        ];
    }

    /** @return array<int, callable> */
    protected function shopProductCatalogAfter(?ShopProduct $product = null): array
    {
        return [function (Validator $validator): void {
            $options = collect((array) $this->input('options'))
                ->filter(fn (mixed $option): bool => is_array($option))
                ->values();
            $variants = collect((array) $this->input('variants'))
                ->filter(fn (mixed $variant): bool => is_array($variant))
                ->values();

            if ($variants->isNotEmpty() && $options->isEmpty()) {
                $validator->errors()->add('options', 'Sản phẩm có biến thể phải khai báo ít nhất một thuộc tính.');
            }

            if ($variants->isEmpty() && $options->isNotEmpty()) {
                $validator->errors()->add('variants', 'Vui lòng tạo biến thể cho các thuộc tính sản phẩm.');
            }

            $optionNames = [];
            $allowedValues = [];

            foreach ($options as $optionIndex => $option) {
                $name = (string) ($option['name'] ?? '');
                $nameKey = $this->catalogKey($name);

                if ($nameKey === '') {
                    continue;
                }

                if (isset($optionNames[$nameKey])) {
                    $validator->errors()->add("options.{$optionIndex}.name", 'Tên thuộc tính không được trùng nhau.');
                }

                $optionNames[$nameKey] = $name;
                $allowedValues[$nameKey] = [];

                foreach ((array) ($option['values'] ?? []) as $valueIndex => $value) {
                    $valueKey = $this->catalogKey((string) $value);

                    if ($valueKey === '') {
                        continue;
                    }

                    if (isset($allowedValues[$nameKey][$valueKey])) {
                        $validator->errors()->add(
                            "options.{$optionIndex}.values.{$valueIndex}",
                            'Giá trị thuộc tính không được trùng nhau.',
                        );
                    }

                    $allowedValues[$nameKey][$valueKey] = (string) $value;
                }
            }

            $combinationKeys = [];
            $defaultCount = 0;
            foreach ($variants as $variantIndex => $variant) {
                $optionValues = (array) ($variant['option_values'] ?? []);
                $providedValues = [];

                foreach ($optionValues as $name => $value) {
                    $providedValues[$this->catalogKey((string) $name)] = (string) $value;
                }

                if (array_diff_key($providedValues, $optionNames) !== []) {
                    $validator->errors()->add(
                        "variants.{$variantIndex}.option_values",
                        'Biến thể chứa thuộc tính không thuộc sản phẩm.',
                    );
                }

                $combination = [];

                foreach ($optionNames as $nameKey => $name) {
                    $value = $providedValues[$nameKey] ?? '';
                    $valueKey = $this->catalogKey($value);

                    if ($valueKey === '' || ! isset($allowedValues[$nameKey][$valueKey])) {
                        $validator->errors()->add(
                            "variants.{$variantIndex}.option_values",
                            "Vui lòng chọn giá trị hợp lệ cho {$name}.",
                        );
                    }

                    $combination[] = $nameKey.'='.$valueKey;
                }

                if (count($providedValues) !== count($optionNames)) {
                    $validator->errors()->add(
                        "variants.{$variantIndex}.option_values",
                        'Mỗi biến thể phải có đúng một giá trị cho từng thuộc tính.',
                    );
                }

                $combinationKey = implode('|', $combination);

                if ($combinationKey !== '' && isset($combinationKeys[$combinationKey])) {
                    $validator->errors()->add(
                        "variants.{$variantIndex}.option_values",
                        'Tổ hợp biến thể đã bị trùng.',
                    );
                }

                $combinationKeys[$combinationKey] = true;

                if ($this->booleanInput($variant['is_default'] ?? false)) {
                    $defaultCount++;

                    if (! $this->booleanInput($variant['is_active'] ?? false)) {
                        $validator->errors()->add(
                            "variants.{$variantIndex}.is_default",
                            'Biến thể mặc định phải đang được bán.',
                        );
                    }
                }
            }

            if ($variants->isNotEmpty() && $defaultCount !== 1) {
                $validator->errors()->add('variants', 'Sản phẩm phải có đúng một biến thể mặc định đang bán.');
            }
        }];
    }

    protected function normalizeShopProductCurrency(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return $value;
        }

        return preg_replace('/\D/', '', (string) $value);
    }

    /** @param array<int, mixed> $variants @return list<array{name: string, values: list<string>}> */
    private function legacyOptions(array $variants): array
    {
        $options = [];
        $colors = $this->legacyValues($variants, 'color');
        $sizes = $this->legacyValues($variants, 'size');

        if ($colors !== []) {
            $options[] = ['name' => 'Màu sắc', 'values' => $colors];
        }

        if ($sizes !== []) {
            $options[] = ['name' => 'Kích thước', 'values' => $sizes];
        }

        return $options;
    }

    /** @param array<int, mixed> $variants @return list<string> */
    private function legacyValues(array $variants, string $field): array
    {
        $seen = [];
        $values = [];

        foreach ($variants as $variant) {
            $value = is_array($variant) ? Str::squish((string) ($variant[$field] ?? '')) : '';
            $key = $this->catalogKey($value);

            if ($key !== '' && ! isset($seen[$key])) {
                $seen[$key] = true;
                $values[] = $value;
            }
        }

        return $values;
    }

    private function applyLegacyOptionValue(array &$optionValues, $canonicalNames, array $aliases, mixed $value): void
    {
        if (! filled($value)) {
            return;
        }

        foreach ($aliases as $alias) {
            $canonicalName = $canonicalNames->get($this->catalogKey($alias));

            if ($canonicalName !== null) {
                $optionValues[$canonicalName] = Str::squish((string) $value);

                return;
            }
        }
    }

    private function catalogKey(string $value): string
    {
        return Str::lower(Str::ascii(Str::squish($value)));
    }

    private function booleanInput(mixed $value): bool
    {
        return in_array($value, [true, 1, '1', 'true', 'on', 'yes'], true);
    }
}
