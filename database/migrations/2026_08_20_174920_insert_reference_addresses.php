<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $addresses = array_map(
            fn (array $address): array => Arr::only($address, [
                'id',
                'code',
                'name',
                'division_type',
                'parent_code',
            ]),
            $this->referenceAddresses(),
        );

        foreach (array_chunk($addresses, 1000) as $chunk) {
            DB::table('addresses')->insert($chunk);
        }
    }

    public function down(): void
    {
        DB::table('addresses')->delete();
    }

    /**
     * @return array<int, array{
     *     id: int,
     *     code: int,
     *     name: string,
     *     codename: ?string,
     *     division_type: string,
     *     phone_code: ?int,
     *     parent_code: ?int
     * }>
     */
    private function referenceAddresses(): array
    {
        return json_decode(<<<'JSON'
[
    {
        "id": 1,
        "code": 1,
        "name": "Thành phố Hà Nội",
        "codename": "ha_noi",
        "division_type": "thành phố trung ương",
        "phone_code": 24,
        "parent_code": null
    },
    {
        "id": 2,
        "code": 4,
        "name": "Tỉnh Cao Bằng",
        "codename": "cao_bang",
        "division_type": "tỉnh",
        "phone_code": 206,
        "parent_code": null
    },
    {
        "id": 3,
        "code": 8,
        "name": "Tỉnh Tuyên Quang",
        "codename": "tuyen_quang",
        "division_type": "tỉnh",
        "phone_code": 207,
        "parent_code": null
    },
    {
        "id": 4,
        "code": 25,
        "name": "Tỉnh Phú Thọ",
        "codename": "phu_tho",
        "division_type": "tỉnh",
        "phone_code": 210,
        "parent_code": null
    },
    {
        "id": 5,
        "code": 70,
        "name": "Phường Hoàn Kiếm",
        "codename": "phuong_hoan_kiem",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 6,
        "code": 82,
        "name": "Tỉnh Đồng Tháp",
        "codename": "dong_thap",
        "division_type": "tỉnh",
        "phone_code": 277,
        "parent_code": null
    },
    {
        "id": 7,
        "code": 91,
        "name": "Tỉnh An Giang",
        "codename": "an_giang",
        "division_type": "tỉnh",
        "phone_code": 296,
        "parent_code": null
    },
    {
        "id": 8,
        "code": 97,
        "name": "Phường Hồng Hà",
        "codename": "phuong_hong_ha",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 9,
        "code": 103,
        "name": "Phường Tây Hồ",
        "codename": "phuong_tay_ho",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 10,
        "code": 118,
        "name": "Phường Bồ Đề",
        "codename": "phuong_bo_de",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 11,
        "code": 127,
        "name": "Phường Việt Hưng",
        "codename": "phuong_viet_hung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 12,
        "code": 136,
        "name": "Phường Phúc Lợi",
        "codename": "phuong_phuc_loi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 13,
        "code": 145,
        "name": "Phường Long Biên",
        "codename": "phuong_long_bien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 14,
        "code": 160,
        "name": "Phường Nghĩa Đô",
        "codename": "phuong_nghia_do",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 15,
        "code": 166,
        "name": "Phường Cầu Giấy",
        "codename": "phuong_cau_giay",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 16,
        "code": 175,
        "name": "Phường Yên Hòa",
        "codename": "phuong_yen_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 17,
        "code": 190,
        "name": "Phường Ô Chợ Dừa",
        "codename": "phuong_o_cho_dua",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 18,
        "code": 199,
        "name": "Phường Láng",
        "codename": "phuong_lang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 19,
        "code": 226,
        "name": "Phường Văn Miếu - Quốc Tử Giám",
        "codename": "phuong_van_mieu_quoc_tu_giam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 20,
        "code": 229,
        "name": "Phường Kim Liên",
        "codename": "phuong_kim_lien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 21,
        "code": 235,
        "name": "Phường Đống Đa",
        "codename": "phuong_dong_da",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 22,
        "code": 256,
        "name": "Phường Hai Bà Trưng",
        "codename": "phuong_hai_ba_trung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 23,
        "code": 283,
        "name": "Phường Vĩnh Tuy",
        "codename": "phuong_vinh_tuy",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 24,
        "code": 292,
        "name": "Phường Bạch Mai",
        "codename": "phuong_bach_mai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 25,
        "code": 301,
        "name": "Phường Vĩnh Hưng",
        "codename": "phuong_vinh_hung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 26,
        "code": 316,
        "name": "Phường Định Công",
        "codename": "phuong_dinh_cong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 27,
        "code": 322,
        "name": "Phường Tương Mai",
        "codename": "phuong_tuong_mai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 28,
        "code": 328,
        "name": "Phường Lĩnh Nam",
        "codename": "phuong_linh_nam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 29,
        "code": 331,
        "name": "Phường Hoàng Mai",
        "codename": "phuong_hoang_mai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 30,
        "code": 337,
        "name": "Phường Hoàng Liệt",
        "codename": "phuong_hoang_liet",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 31,
        "code": 340,
        "name": "Phường Yên Sở",
        "codename": "phuong_yen_so",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 32,
        "code": 352,
        "name": "Phường Phương Liệt",
        "codename": "phuong_phuong_liet",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 33,
        "code": 364,
        "name": "Phường Khương Đình",
        "codename": "phuong_khuong_dinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 34,
        "code": 367,
        "name": "Phường Thanh Xuân",
        "codename": "phuong_thanh_xuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 35,
        "code": 376,
        "name": "Xã Sóc Sơn",
        "codename": "xa_soc_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 36,
        "code": 382,
        "name": "Xã Kim Anh",
        "codename": "xa_kim_anh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 37,
        "code": 385,
        "name": "Xã Trung Giã",
        "codename": "xa_trung_gia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 38,
        "code": 430,
        "name": "Xã Đa Phúc",
        "codename": "xa_da_phuc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 39,
        "code": 433,
        "name": "Xã Nội Bài",
        "codename": "xa_noi_bai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 40,
        "code": 454,
        "name": "Xã Đông Anh",
        "codename": "xa_dong_anh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 41,
        "code": 466,
        "name": "Xã Phúc Thịnh",
        "codename": "xa_phuc_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 42,
        "code": 475,
        "name": "Xã Thư Lâm",
        "codename": "xa_thu_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 43,
        "code": 493,
        "name": "Xã Thiên Lộc",
        "codename": "xa_thien_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 44,
        "code": 508,
        "name": "Xã Vĩnh Thanh",
        "codename": "xa_vinh_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 45,
        "code": 541,
        "name": "Xã Phù Đổng",
        "codename": "xa_phu_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 46,
        "code": 562,
        "name": "Xã Thuận An",
        "codename": "xa_thuan_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 47,
        "code": 565,
        "name": "Xã Gia Lâm",
        "codename": "xa_gia_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 48,
        "code": 577,
        "name": "Xã Bát Tràng",
        "codename": "xa_bat_trang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 49,
        "code": 592,
        "name": "Phường Từ Liêm",
        "codename": "phuong_tu_liem",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 50,
        "code": 598,
        "name": "Phường Thượng Cát",
        "codename": "phuong_thuong_cat",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 51,
        "code": 602,
        "name": "Phường Đông Ngạc",
        "codename": "phuong_dong_ngac",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 52,
        "code": 611,
        "name": "Phường Xuân Đỉnh",
        "codename": "phuong_xuan_dinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 53,
        "code": 613,
        "name": "Phường Tây Tựu",
        "codename": "phuong_tay_tuu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 54,
        "code": 619,
        "name": "Phường Phú Diễn",
        "codename": "phuong_phu_dien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 55,
        "code": 622,
        "name": "Phường Xuân Phương",
        "codename": "phuong_xuan_phuong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 56,
        "code": 634,
        "name": "Phường Tây Mỗ",
        "codename": "phuong_tay_mo",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 57,
        "code": 637,
        "name": "Phường Đại Mỗ",
        "codename": "phuong_dai_mo",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 58,
        "code": 640,
        "name": "Xã Thanh Trì",
        "codename": "xa_thanh_tri",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 59,
        "code": 643,
        "name": "Phường Thanh Liệt",
        "codename": "phuong_thanh_liet",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 60,
        "code": 664,
        "name": "Xã Đại Thanh",
        "codename": "xa_dai_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 61,
        "code": 679,
        "name": "Xã Ngọc Hồi",
        "codename": "xa_ngoc_hoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 62,
        "code": 685,
        "name": "Xã Nam Phù",
        "codename": "xa_nam_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 63,
        "code": 4930,
        "name": "Xã Yên Xuân",
        "codename": "xa_yen_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 64,
        "code": 8974,
        "name": "Xã Quang Minh",
        "codename": "xa_quang_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 65,
        "code": 8980,
        "name": "Xã Yên Lãng",
        "codename": "xa_yen_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 66,
        "code": 8995,
        "name": "Xã Tiến Thắng",
        "codename": "xa_tien_thang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 67,
        "code": 9022,
        "name": "Xã Mê Linh",
        "codename": "xa_me_linh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 68,
        "code": 9552,
        "name": "Phường Kiến Hưng",
        "codename": "phuong_kien_hung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 69,
        "code": 9556,
        "name": "Phường Hà Đông",
        "codename": "phuong_ha_dong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 70,
        "code": 9562,
        "name": "Phường Yên Nghĩa",
        "codename": "phuong_yen_nghia",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 71,
        "code": 9568,
        "name": "Phường Phú Lương",
        "codename": "phuong_phu_luong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 72,
        "code": 9574,
        "name": "Phường Sơn Tây",
        "codename": "phuong_son_tay",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 73,
        "code": 9604,
        "name": "Phường Tùng Thiện",
        "codename": "phuong_tung_thien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 74,
        "code": 9616,
        "name": "Xã Đoài Phương",
        "codename": "xa_doai_phuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 75,
        "code": 9619,
        "name": "Xã Quảng Oai",
        "codename": "xa_quang_oai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 76,
        "code": 9634,
        "name": "Xã Cổ Đô",
        "codename": "xa_co_do",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 77,
        "code": 9661,
        "name": "Xã Minh Châu",
        "codename": "xa_minh_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 78,
        "code": 9664,
        "name": "Xã Vật Lại",
        "codename": "xa_vat_lai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 79,
        "code": 9676,
        "name": "Xã Bất Bạt",
        "codename": "xa_bat_bat",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 80,
        "code": 9694,
        "name": "Xã Suối Hai",
        "codename": "xa_suoi_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 81,
        "code": 9700,
        "name": "Xã Ba Vì",
        "codename": "xa_ba_vi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 82,
        "code": 9706,
        "name": "Xã Yên Bài",
        "codename": "xa_yen_bai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 83,
        "code": 9715,
        "name": "Xã Phúc Thọ",
        "codename": "xa_phuc_tho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 84,
        "code": 9739,
        "name": "Xã Phúc Lộc",
        "codename": "xa_phuc_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 85,
        "code": 9772,
        "name": "Xã Hát Môn",
        "codename": "xa_hat_mon",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 86,
        "code": 9784,
        "name": "Xã Đan Phượng",
        "codename": "xa_dan_phuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 87,
        "code": 9787,
        "name": "Xã Liên Minh",
        "codename": "xa_lien_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 88,
        "code": 9817,
        "name": "Xã Ô Diên",
        "codename": "xa_o_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 89,
        "code": 9832,
        "name": "Xã Hoài Đức",
        "codename": "xa_hoai_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 90,
        "code": 9856,
        "name": "Xã Dương Hòa",
        "codename": "xa_duong_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 91,
        "code": 9871,
        "name": "Xã Sơn Đồng",
        "codename": "xa_son_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 92,
        "code": 9877,
        "name": "Xã An Khánh",
        "codename": "xa_an_khanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 93,
        "code": 9886,
        "name": "Phường Dương Nội",
        "codename": "phuong_duong_noi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 94,
        "code": 9895,
        "name": "Xã Quốc Oai",
        "codename": "xa_quoc_oai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 95,
        "code": 9910,
        "name": "Xã Kiều Phú",
        "codename": "xa_kieu_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 96,
        "code": 9931,
        "name": "Xã Hưng Đạo",
        "codename": "xa_hung_dao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 97,
        "code": 9952,
        "name": "Xã Phú Cát",
        "codename": "xa_phu_cat",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 98,
        "code": 9955,
        "name": "Xã Thạch Thất",
        "codename": "xa_thach_that",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 99,
        "code": 9982,
        "name": "Xã Hạ Bằng",
        "codename": "xa_ha_bang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 100,
        "code": 9988,
        "name": "Xã Hòa Lạc",
        "codename": "xa_hoa_lac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 101,
        "code": 10003,
        "name": "Xã Tây Phương",
        "codename": "xa_tay_phuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 102,
        "code": 10015,
        "name": "Phường Chương Mỹ",
        "codename": "phuong_chuong_my",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 103,
        "code": 10030,
        "name": "Xã Phú Nghĩa",
        "codename": "xa_phu_nghia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 104,
        "code": 10045,
        "name": "Xã Xuân Mai",
        "codename": "xa_xuan_mai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 105,
        "code": 10072,
        "name": "Xã Quảng Bị",
        "codename": "xa_quang_bi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 106,
        "code": 10081,
        "name": "Xã Trần Phú",
        "codename": "xa_tran_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 107,
        "code": 10096,
        "name": "Xã Hòa Phú",
        "codename": "xa_hoa_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 108,
        "code": 10114,
        "name": "Xã Thanh Oai",
        "codename": "xa_thanh_oai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 109,
        "code": 10126,
        "name": "Xã Bình Minh",
        "codename": "xa_binh_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 110,
        "code": 10144,
        "name": "Xã Tam Hưng",
        "codename": "xa_tam_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 111,
        "code": 10180,
        "name": "Xã Dân Hòa",
        "codename": "xa_dan_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 112,
        "code": 10183,
        "name": "Xã Thường Tín",
        "codename": "xa_thuong_tin",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 113,
        "code": 10210,
        "name": "Xã Hồng Vân",
        "codename": "xa_hong_van",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 114,
        "code": 10231,
        "name": "Xã Thượng Phúc",
        "codename": "xa_thuong_phuc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 115,
        "code": 10237,
        "name": "Xã Chương Dương",
        "codename": "xa_chuong_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 116,
        "code": 10273,
        "name": "Xã Phú Xuyên",
        "codename": "xa_phu_xuyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 117,
        "code": 10279,
        "name": "Xã Phượng Dực",
        "codename": "xa_phuong_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 118,
        "code": 10330,
        "name": "Xã Chuyên Mỹ",
        "codename": "xa_chuyen_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 119,
        "code": 10342,
        "name": "Xã Đại Xuyên",
        "codename": "xa_dai_xuyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 120,
        "code": 10354,
        "name": "Xã Vân Đình",
        "codename": "xa_van_dinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 121,
        "code": 10369,
        "name": "Xã Ứng Thiên",
        "codename": "xa_ung_thien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 122,
        "code": 10402,
        "name": "Xã Ứng Hòa",
        "codename": "xa_ung_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 123,
        "code": 10417,
        "name": "Xã Hòa Xá",
        "codename": "xa_hoa_xa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 124,
        "code": 10441,
        "name": "Xã Mỹ Đức",
        "codename": "xa_my_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 125,
        "code": 10459,
        "name": "Xã Phúc Sơn",
        "codename": "xa_phuc_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 126,
        "code": 10465,
        "name": "Xã Hồng Sơn",
        "codename": "xa_hong_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 127,
        "code": 10489,
        "name": "Xã Hương Sơn",
        "codename": "xa_huong_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 1
    },
    {
        "id": 129,
        "code": 1273,
        "name": "Phường Thục Phán",
        "codename": "phuong_thuc_phan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 130,
        "code": 1279,
        "name": "Phường Nùng Trí Cao",
        "codename": "phuong_nung_tri_cao",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 131,
        "code": 1288,
        "name": "Phường Tân Giang",
        "codename": "phuong_tan_giang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 132,
        "code": 1290,
        "name": "Xã Bảo Lâm",
        "codename": "xa_bao_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 133,
        "code": 1294,
        "name": "Xã Lý Bôn",
        "codename": "xa_ly_bon",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 134,
        "code": 1297,
        "name": "Xã Nam Quang",
        "codename": "xa_nam_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 135,
        "code": 1304,
        "name": "Xã Quảng Lâm",
        "codename": "xa_quang_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 136,
        "code": 1318,
        "name": "Xã Yên Thổ",
        "codename": "xa_yen_tho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 137,
        "code": 1321,
        "name": "Xã Bảo Lạc",
        "codename": "xa_bao_lac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 138,
        "code": 1324,
        "name": "Xã Cốc Pàng",
        "codename": "xa_coc_pang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 139,
        "code": 1327,
        "name": "Xã Cô Ba",
        "codename": "xa_co_ba",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 140,
        "code": 1336,
        "name": "Xã Khánh Xuân",
        "codename": "xa_khanh_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 141,
        "code": 1339,
        "name": "Xã Xuân Trường",
        "codename": "xa_xuan_truong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 142,
        "code": 1351,
        "name": "Xã Hưng Đạo",
        "codename": "xa_hung_dao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 143,
        "code": 1354,
        "name": "Xã Huy Giáp",
        "codename": "xa_huy_giap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 144,
        "code": 1360,
        "name": "Xã Sơn Lộ",
        "codename": "xa_son_lo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 145,
        "code": 1363,
        "name": "Xã Thông Nông",
        "codename": "xa_thong_nong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 146,
        "code": 1366,
        "name": "Xã Cần Yên",
        "codename": "xa_can_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 147,
        "code": 1387,
        "name": "Xã Thanh Long",
        "codename": "xa_thanh_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 148,
        "code": 1392,
        "name": "Xã Trường Hà",
        "codename": "xa_truong_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 149,
        "code": 1393,
        "name": "Xã Lũng Nặm",
        "codename": "xa_lung_nam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 150,
        "code": 1414,
        "name": "Xã Tổng Cọt",
        "codename": "xa_tong_cot",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 151,
        "code": 1438,
        "name": "Xã Hà Quảng",
        "codename": "xa_ha_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 152,
        "code": 1447,
        "name": "Xã Trà Lĩnh",
        "codename": "xa_tra_linh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 153,
        "code": 1456,
        "name": "Xã Quang Hán",
        "codename": "xa_quang_han",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 154,
        "code": 1465,
        "name": "Xã Quang Trung",
        "codename": "xa_quang_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 155,
        "code": 1477,
        "name": "Xã Trùng Khánh",
        "codename": "xa_trung_khanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 156,
        "code": 1489,
        "name": "Xã Đình Phong",
        "codename": "xa_dinh_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 157,
        "code": 1501,
        "name": "Xã Đàm Thủy",
        "codename": "xa_dam_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 158,
        "code": 1525,
        "name": "Xã Đoài Dương",
        "codename": "xa_doai_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 159,
        "code": 1537,
        "name": "Xã Lý Quốc",
        "codename": "xa_ly_quoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 160,
        "code": 1552,
        "name": "Xã Quang Long",
        "codename": "xa_quang_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 161,
        "code": 1558,
        "name": "Xã Hạ Lang",
        "codename": "xa_ha_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 162,
        "code": 1561,
        "name": "Xã Vinh Quý",
        "codename": "xa_vinh_quy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 163,
        "code": 1576,
        "name": "Xã Quảng Uyên",
        "codename": "xa_quang_uyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 164,
        "code": 1594,
        "name": "Xã Độc Lập",
        "codename": "xa_doc_lap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 165,
        "code": 1618,
        "name": "Xã Hạnh Phúc",
        "codename": "xa_hanh_phuc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 166,
        "code": 1636,
        "name": "Xã Bế Văn Đàn",
        "codename": "xa_be_van_dan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 167,
        "code": 1648,
        "name": "Xã Phục Hòa",
        "codename": "xa_phuc_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 168,
        "code": 1654,
        "name": "Xã Hòa An",
        "codename": "xa_hoa_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 169,
        "code": 1660,
        "name": "Xã Nam Tuấn",
        "codename": "xa_nam_tuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 170,
        "code": 1699,
        "name": "Xã Nguyễn Huệ",
        "codename": "xa_nguyen_hue",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 171,
        "code": 1708,
        "name": "Xã Bạch Đằng",
        "codename": "xa_bach_dang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 172,
        "code": 1726,
        "name": "Xã Nguyên Bình",
        "codename": "xa_nguyen_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 173,
        "code": 1729,
        "name": "Xã Tĩnh Túc",
        "codename": "xa_tinh_tuc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 174,
        "code": 1738,
        "name": "Xã Ca Thành",
        "codename": "xa_ca_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 175,
        "code": 1747,
        "name": "Xã Minh Tâm",
        "codename": "xa_minh_tam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 176,
        "code": 1768,
        "name": "Xã Phan Thanh",
        "codename": "xa_phan_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 177,
        "code": 1774,
        "name": "Xã Tam Kim",
        "codename": "xa_tam_kim",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 178,
        "code": 1777,
        "name": "Xã Thành Công",
        "codename": "xa_thanh_cong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 179,
        "code": 1786,
        "name": "Xã Đông Khê",
        "codename": "xa_dong_khe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 180,
        "code": 1789,
        "name": "Xã Canh Tân",
        "codename": "xa_canh_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 181,
        "code": 1792,
        "name": "Xã Kim Đồng",
        "codename": "xa_kim_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 182,
        "code": 1795,
        "name": "Xã Minh Khai",
        "codename": "xa_minh_khai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 183,
        "code": 1807,
        "name": "Xã Thạch An",
        "codename": "xa_thach_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 184,
        "code": 1822,
        "name": "Xã Đức Long",
        "codename": "xa_duc_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 4
    },
    {
        "id": 186,
        "code": 691,
        "name": "Phường Hà Giang 2",
        "codename": "phuong_ha_giang_2",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 187,
        "code": 694,
        "name": "Phường Hà Giang 1",
        "codename": "phuong_ha_giang_1",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 188,
        "code": 700,
        "name": "Xã Ngọc Đường",
        "codename": "xa_ngoc_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 189,
        "code": 706,
        "name": "Xã Phú Linh",
        "codename": "xa_phu_linh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 190,
        "code": 715,
        "name": "Xã Lũng Cú",
        "codename": "xa_lung_cu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 191,
        "code": 721,
        "name": "Xã Đồng Văn",
        "codename": "xa_dong_van",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 192,
        "code": 733,
        "name": "Xã Sà Phìn",
        "codename": "xa_sa_phin",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 193,
        "code": 745,
        "name": "Xã Phố Bảng",
        "codename": "xa_pho_bang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 194,
        "code": 763,
        "name": "Xã Lũng Phìn",
        "codename": "xa_lung_phin",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 195,
        "code": 769,
        "name": "Xã Mèo Vạc",
        "codename": "xa_meo_vac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 196,
        "code": 778,
        "name": "Xã Sơn Vĩ",
        "codename": "xa_son_vi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 197,
        "code": 787,
        "name": "Xã Sủng Máng",
        "codename": "xa_sung_mang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 198,
        "code": 802,
        "name": "Xã Khâu Vai",
        "codename": "xa_khau_vai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 199,
        "code": 808,
        "name": "Xã Tát Ngà",
        "codename": "xa_tat_nga",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 200,
        "code": 817,
        "name": "Xã Niêm Sơn",
        "codename": "xa_niem_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 201,
        "code": 820,
        "name": "Xã Yên Minh",
        "codename": "xa_yen_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 202,
        "code": 829,
        "name": "Xã Thắng Mố",
        "codename": "xa_thang_mo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 203,
        "code": 832,
        "name": "Xã Bạch Đích",
        "codename": "xa_bach_dich",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 204,
        "code": 847,
        "name": "Xã Mậu Duệ",
        "codename": "xa_mau_due",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 205,
        "code": 859,
        "name": "Xã Ngọc Long",
        "codename": "xa_ngoc_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 206,
        "code": 865,
        "name": "Xã Đường Thượng",
        "codename": "xa_duong_thuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 207,
        "code": 871,
        "name": "Xã Du Già",
        "codename": "xa_du_gia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 208,
        "code": 874,
        "name": "Xã Quản Bạ",
        "codename": "xa_quan_ba",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 209,
        "code": 883,
        "name": "Xã Cán Tỷ",
        "codename": "xa_can_ty",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 210,
        "code": 889,
        "name": "Xã Nghĩa Thuận",
        "codename": "xa_nghia_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 211,
        "code": 892,
        "name": "Xã Tùng Vài",
        "codename": "xa_tung_vai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 212,
        "code": 901,
        "name": "Xã Lùng Tám",
        "codename": "xa_lung_tam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 213,
        "code": 913,
        "name": "Xã Vị Xuyên",
        "codename": "xa_vi_xuyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 214,
        "code": 919,
        "name": "Xã Minh Tân",
        "codename": "xa_minh_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 215,
        "code": 922,
        "name": "Xã Thuận Hoà",
        "codename": "xa_thuan_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 216,
        "code": 925,
        "name": "Xã Tùng Bá",
        "codename": "xa_tung_ba",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 217,
        "code": 928,
        "name": "Xã Thanh Thủy",
        "codename": "xa_thanh_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 218,
        "code": 937,
        "name": "Xã Lao Chải",
        "codename": "xa_lao_chai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 219,
        "code": 952,
        "name": "Xã Cao Bồ",
        "codename": "xa_cao_bo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 220,
        "code": 958,
        "name": "Xã Thượng Sơn",
        "codename": "xa_thuong_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 221,
        "code": 967,
        "name": "Xã Việt Lâm",
        "codename": "xa_viet_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 222,
        "code": 970,
        "name": "Xã Linh Hồ",
        "codename": "xa_linh_ho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 223,
        "code": 976,
        "name": "Xã Bạch Ngọc",
        "codename": "xa_bach_ngoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 224,
        "code": 982,
        "name": "Xã Minh Sơn",
        "codename": "xa_minh_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 225,
        "code": 985,
        "name": "Xã Giáp Trung",
        "codename": "xa_giap_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 226,
        "code": 991,
        "name": "Xã Bắc Mê",
        "codename": "xa_bac_me",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 227,
        "code": 994,
        "name": "Xã Minh Ngọc",
        "codename": "xa_minh_ngoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 228,
        "code": 1006,
        "name": "Xã Yên Cường",
        "codename": "xa_yen_cuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 229,
        "code": 1012,
        "name": "Xã Đường Hồng",
        "codename": "xa_duong_hong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 230,
        "code": 1021,
        "name": "Xã Hoàng Su Phì",
        "codename": "xa_hoang_su_phi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 231,
        "code": 1024,
        "name": "Xã Bản Máy",
        "codename": "xa_ban_may",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 232,
        "code": 1033,
        "name": "Xã Thàng Tín",
        "codename": "xa_thang_tin",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 233,
        "code": 1051,
        "name": "Xã Tân Tiến",
        "codename": "xa_tan_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 234,
        "code": 1057,
        "name": "Xã Pờ Ly Ngài",
        "codename": "xa_po_ly_ngai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 235,
        "code": 1075,
        "name": "Xã Nậm Dịch",
        "codename": "xa_nam_dich",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 236,
        "code": 1084,
        "name": "Xã Hồ Thầu",
        "codename": "xa_ho_thau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 237,
        "code": 1090,
        "name": "Xã Thông Nguyên",
        "codename": "xa_thong_nguyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 238,
        "code": 1096,
        "name": "Xã Pà Vầy Sủ",
        "codename": "xa_pa_vay_su",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 239,
        "code": 1108,
        "name": "Xã Xín Mần",
        "codename": "xa_xin_man",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 240,
        "code": 1117,
        "name": "Xã Trung Thịnh",
        "codename": "xa_trung_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 241,
        "code": 1141,
        "name": "Xã Nấm Dẩn",
        "codename": "xa_nam_dan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 242,
        "code": 1144,
        "name": "Xã Quảng Nguyên",
        "codename": "xa_quang_nguyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 243,
        "code": 1147,
        "name": "Xã Khuôn Lùng",
        "codename": "xa_khuon_lung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 244,
        "code": 1153,
        "name": "Xã Bắc Quang",
        "codename": "xa_bac_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 245,
        "code": 1156,
        "name": "Xã Vĩnh Tuy",
        "codename": "xa_vinh_tuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 246,
        "code": 1165,
        "name": "Xã Đồng Tâm",
        "codename": "xa_dong_tam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 247,
        "code": 1171,
        "name": "Xã Tân Quang",
        "codename": "xa_tan_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 248,
        "code": 1180,
        "name": "Xã Bằng Hành",
        "codename": "xa_bang_hanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 249,
        "code": 1192,
        "name": "Xã Liên Hiệp",
        "codename": "xa_lien_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 250,
        "code": 1201,
        "name": "Xã Hùng An",
        "codename": "xa_hung_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 251,
        "code": 1216,
        "name": "Xã Đồng Yên",
        "codename": "xa_dong_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 252,
        "code": 1225,
        "name": "Xã Tiên Nguyên",
        "codename": "xa_tien_nguyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 253,
        "code": 1234,
        "name": "Xã Yên Thành",
        "codename": "xa_yen_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 254,
        "code": 1237,
        "name": "Xã Quang Bình",
        "codename": "xa_quang_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 255,
        "code": 1243,
        "name": "Xã Tân Trịnh",
        "codename": "xa_tan_trinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 256,
        "code": 1246,
        "name": "Xã Bằng Lang",
        "codename": "xa_bang_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 257,
        "code": 1255,
        "name": "Xã Xuân Giang",
        "codename": "xa_xuan_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 258,
        "code": 1261,
        "name": "Xã Tiên Yên",
        "codename": "xa_tien_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 259,
        "code": 2212,
        "name": "Phường Nông Tiến",
        "codename": "phuong_nong_tien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 260,
        "code": 2215,
        "name": "Phường Minh Xuân",
        "codename": "phuong_minh_xuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 261,
        "code": 2221,
        "name": "Xã Nà Hang",
        "codename": "xa_na_hang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 262,
        "code": 2239,
        "name": "Xã Thượng Nông",
        "codename": "xa_thuong_nong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 263,
        "code": 2245,
        "name": "Xã Côn Lôn",
        "codename": "xa_con_lon",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 264,
        "code": 2248,
        "name": "Xã Yên Hoa",
        "codename": "xa_yen_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 265,
        "code": 2260,
        "name": "Xã Hồng Thái",
        "codename": "xa_hong_thai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 266,
        "code": 2266,
        "name": "Xã Lâm Bình",
        "codename": "xa_lam_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 267,
        "code": 2269,
        "name": "Xã Thượng Lâm",
        "codename": "xa_thuong_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 268,
        "code": 2287,
        "name": "Xã Chiêm Hoá",
        "codename": "xa_chiem_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 269,
        "code": 2296,
        "name": "Xã Bình An",
        "codename": "xa_binh_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 270,
        "code": 2302,
        "name": "Xã Minh Quang",
        "codename": "xa_minh_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 271,
        "code": 2305,
        "name": "Xã Trung Hà",
        "codename": "xa_trung_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 272,
        "code": 2308,
        "name": "Xã Tân Mỹ",
        "codename": "xa_tan_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 273,
        "code": 2317,
        "name": "Xã Yên Lập",
        "codename": "xa_yen_lap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 274,
        "code": 2320,
        "name": "Xã Tân An",
        "codename": "xa_tan_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 275,
        "code": 2332,
        "name": "Xã Kiên Đài",
        "codename": "xa_kien_dai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 276,
        "code": 2350,
        "name": "Xã Kim Bình",
        "codename": "xa_kim_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 277,
        "code": 2353,
        "name": "Xã Hoà An",
        "codename": "xa_hoa_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 278,
        "code": 2359,
        "name": "Xã Tri Phú",
        "codename": "xa_tri_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 279,
        "code": 2365,
        "name": "Xã Yên Nguyên",
        "codename": "xa_yen_nguyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 280,
        "code": 2374,
        "name": "Xã Hàm Yên",
        "codename": "xa_ham_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 281,
        "code": 2380,
        "name": "Xã Bạch Xa",
        "codename": "xa_bach_xa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 282,
        "code": 2392,
        "name": "Xã Phù Lưu",
        "codename": "xa_phu_luu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 283,
        "code": 2398,
        "name": "Xã Yên Phú",
        "codename": "xa_yen_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 284,
        "code": 2404,
        "name": "Xã Bình Xa",
        "codename": "xa_binh_xa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 285,
        "code": 2407,
        "name": "Xã Thái Sơn",
        "codename": "xa_thai_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 286,
        "code": 2419,
        "name": "Xã Thái Hoà",
        "codename": "xa_thai_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 287,
        "code": 2425,
        "name": "Xã Hùng Đức",
        "codename": "xa_hung_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 288,
        "code": 2434,
        "name": "Xã Lực Hành",
        "codename": "xa_luc_hanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 289,
        "code": 2437,
        "name": "Xã Kiến Thiết",
        "codename": "xa_kien_thiet",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 290,
        "code": 2449,
        "name": "Xã Xuân Vân",
        "codename": "xa_xuan_van",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 291,
        "code": 2455,
        "name": "Xã Hùng Lợi",
        "codename": "xa_hung_loi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 292,
        "code": 2458,
        "name": "Xã Trung Sơn",
        "codename": "xa_trung_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 293,
        "code": 2470,
        "name": "Xã Tân Long",
        "codename": "xa_tan_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 294,
        "code": 2473,
        "name": "Xã Yên Sơn",
        "codename": "xa_yen_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 295,
        "code": 2494,
        "name": "Xã Thái Bình",
        "codename": "xa_thai_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 296,
        "code": 2509,
        "name": "Phường Mỹ Lâm",
        "codename": "phuong_my_lam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 297,
        "code": 2512,
        "name": "Phường An Tường",
        "codename": "phuong_an_tuong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 298,
        "code": 2524,
        "name": "Phường Bình Thuận",
        "codename": "phuong_binh_thuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 299,
        "code": 2530,
        "name": "Xã Nhữ Khê",
        "codename": "xa_nhu_khe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 300,
        "code": 2536,
        "name": "Xã Sơn Dương",
        "codename": "xa_son_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 301,
        "code": 2545,
        "name": "Xã Tân Trào",
        "codename": "xa_tan_trao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 302,
        "code": 2548,
        "name": "Xã Bình Ca",
        "codename": "xa_binh_ca",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 303,
        "code": 2554,
        "name": "Xã Minh Thanh",
        "codename": "xa_minh_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 304,
        "code": 2572,
        "name": "Xã Đông Thọ",
        "codename": "xa_dong_tho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 305,
        "code": 2578,
        "name": "Xã Tân Thanh",
        "codename": "xa_tan_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 306,
        "code": 2608,
        "name": "Xã Hồng Sơn",
        "codename": "xa_hong_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 307,
        "code": 2611,
        "name": "Xã Phú Lương",
        "codename": "xa_phu_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 308,
        "code": 2620,
        "name": "Xã Sơn Thuỷ",
        "codename": "xa_son_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 309,
        "code": 2623,
        "name": "Xã Trường Sinh",
        "codename": "xa_truong_sinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 8
    },
    {
        "id": 310,
        "code": 11,
        "name": "Tỉnh Điện Biên",
        "codename": "dien_bien",
        "division_type": "tỉnh",
        "phone_code": 215,
        "parent_code": null
    },
    {
        "id": 311,
        "code": 3127,
        "name": "Phường Điện Biên Phủ",
        "codename": "phuong_dien_bien_phu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 312,
        "code": 3151,
        "name": "Phường Mường Lay",
        "codename": "phuong_muong_lay",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 313,
        "code": 3158,
        "name": "Xã Sín Thầu",
        "codename": "xa_sin_thau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 314,
        "code": 3160,
        "name": "Xã Mường Nhé",
        "codename": "xa_muong_nhe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 315,
        "code": 3162,
        "name": "Xã Nậm Kè",
        "codename": "xa_nam_ke",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 316,
        "code": 3163,
        "name": "Xã Mường Toong",
        "codename": "xa_muong_toong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 317,
        "code": 3164,
        "name": "Xã Quảng Lâm",
        "codename": "xa_quang_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 318,
        "code": 3166,
        "name": "Xã Mường Chà",
        "codename": "xa_muong_cha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 319,
        "code": 3169,
        "name": "Xã Nà Hỳ",
        "codename": "xa_na_hy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 320,
        "code": 3172,
        "name": "Xã Na Sang",
        "codename": "xa_na_sang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 321,
        "code": 3175,
        "name": "Xã Chà Tở",
        "codename": "xa_cha_to",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 322,
        "code": 3176,
        "name": "Xã Nà Bủng",
        "codename": "xa_na_bung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 323,
        "code": 3181,
        "name": "Xã Mường Tùng",
        "codename": "xa_muong_tung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 324,
        "code": 3193,
        "name": "Xã Pa Ham",
        "codename": "xa_pa_ham",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 325,
        "code": 3194,
        "name": "Xã Nậm Nèn",
        "codename": "xa_nam_nen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 326,
        "code": 3199,
        "name": "Xã Si Pa Phìn",
        "codename": "xa_si_pa_phin",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 327,
        "code": 3202,
        "name": "Xã Mường Pồn",
        "codename": "xa_muong_pon",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 328,
        "code": 3203,
        "name": "Xã Na Son",
        "codename": "xa_na_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 329,
        "code": 3208,
        "name": "Xã Xa Dung",
        "codename": "xa_xa_dung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 330,
        "code": 3214,
        "name": "Xã Mường Luân",
        "codename": "xa_muong_luan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 331,
        "code": 3217,
        "name": "Xã Tủa Chùa",
        "codename": "xa_tua_chua",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 332,
        "code": 3220,
        "name": "Xã Tủa Thàng",
        "codename": "xa_tua_thang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 333,
        "code": 3226,
        "name": "Xã Sín Chải",
        "codename": "xa_sin_chai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 334,
        "code": 3241,
        "name": "Xã Sính Phình",
        "codename": "xa_sinh_phinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 335,
        "code": 3244,
        "name": "Xã Sáng Nhè",
        "codename": "xa_sang_nhe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 336,
        "code": 3253,
        "name": "Xã Tuần Giáo",
        "codename": "xa_tuan_giao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 337,
        "code": 3256,
        "name": "Xã Mường Ảng",
        "codename": "xa_muong_ang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 338,
        "code": 3260,
        "name": "Xã Pú Nhung",
        "codename": "xa_pu_nhung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 339,
        "code": 3268,
        "name": "Xã Mường Mùn",
        "codename": "xa_muong_mun",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 340,
        "code": 3283,
        "name": "Xã Chiềng Sinh",
        "codename": "xa_chieng_sinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 341,
        "code": 3295,
        "name": "Xã Quài Tở",
        "codename": "xa_quai_to",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 342,
        "code": 3301,
        "name": "Xã Búng Lao",
        "codename": "xa_bung_lao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 343,
        "code": 3313,
        "name": "Xã Mường Lạn",
        "codename": "xa_muong_lan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 344,
        "code": 3316,
        "name": "Xã Nà Tấu",
        "codename": "xa_na_tau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 345,
        "code": 3325,
        "name": "Xã Mường Phăng",
        "codename": "xa_muong_phang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 346,
        "code": 3328,
        "name": "Xã Thanh Nưa",
        "codename": "xa_thanh_nua",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 347,
        "code": 3334,
        "name": "Phường Mường Thanh",
        "codename": "phuong_muong_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 348,
        "code": 3349,
        "name": "Xã Thanh Yên",
        "codename": "xa_thanh_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 349,
        "code": 3352,
        "name": "Xã Thanh An",
        "codename": "xa_thanh_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 350,
        "code": 3356,
        "name": "Xã Sam Mứn",
        "codename": "xa_sam_mun",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 351,
        "code": 3358,
        "name": "Xã Núa Ngam",
        "codename": "xa_nua_ngam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 352,
        "code": 3368,
        "name": "Xã Mường Nhà",
        "codename": "xa_muong_nha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 353,
        "code": 3370,
        "name": "Xã Pu Nhi",
        "codename": "xa_pu_nhi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 354,
        "code": 3382,
        "name": "Xã Phình Giàng",
        "codename": "xa_phinh_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 355,
        "code": 3385,
        "name": "Xã Tìa Dình",
        "codename": "xa_tia_dinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 11
    },
    {
        "id": 356,
        "code": 12,
        "name": "Tỉnh Lai Châu",
        "codename": "lai_chau",
        "division_type": "tỉnh",
        "phone_code": 213,
        "parent_code": null
    },
    {
        "id": 357,
        "code": 3388,
        "name": "Phường Đoàn Kết",
        "codename": "phuong_doan_ket",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 358,
        "code": 3390,
        "name": "Xã Bình Lư",
        "codename": "xa_binh_lu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 359,
        "code": 3394,
        "name": "Xã Sin Suối Hồ",
        "codename": "xa_sin_suoi_ho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 360,
        "code": 3405,
        "name": "Xã Tả Lèng",
        "codename": "xa_ta_leng",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 361,
        "code": 3408,
        "name": "Phường Tân Phong",
        "codename": "phuong_tan_phong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 362,
        "code": 3424,
        "name": "Xã Bản Bo",
        "codename": "xa_ban_bo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 363,
        "code": 3430,
        "name": "Xã Khun Há",
        "codename": "xa_khun_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 364,
        "code": 3433,
        "name": "Xã Bum Tở",
        "codename": "xa_bum_to",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 365,
        "code": 3434,
        "name": "Xã Nậm Hàng",
        "codename": "xa_nam_hang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 366,
        "code": 3439,
        "name": "Xã Thu Lũm",
        "codename": "xa_thu_lum",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 367,
        "code": 3442,
        "name": "Xã Pa Ủ",
        "codename": "xa_pa_u",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 368,
        "code": 3445,
        "name": "Xã Mường Tè",
        "codename": "xa_muong_te",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 369,
        "code": 3451,
        "name": "Xã Mù Cả",
        "codename": "xa_mu_ca",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 370,
        "code": 3460,
        "name": "Xã Hua Bum",
        "codename": "xa_hua_bum",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 371,
        "code": 3463,
        "name": "Xã Tà Tổng",
        "codename": "xa_ta_tong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 372,
        "code": 3466,
        "name": "Xã Bum Nưa",
        "codename": "xa_bum_nua",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 373,
        "code": 3472,
        "name": "Xã Mường Mô",
        "codename": "xa_muong_mo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 374,
        "code": 3478,
        "name": "Xã Sìn Hồ",
        "codename": "xa_sin_ho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 375,
        "code": 3487,
        "name": "Xã Lê Lợi",
        "codename": "xa_le_loi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 376,
        "code": 3503,
        "name": "Xã Pa Tần",
        "codename": "xa_pa_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 377,
        "code": 3508,
        "name": "Xã Hồng Thu",
        "codename": "xa_hong_thu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 378,
        "code": 3517,
        "name": "Xã Nậm Tăm",
        "codename": "xa_nam_tam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 379,
        "code": 3529,
        "name": "Xã Tủa Sín Chải",
        "codename": "xa_tua_sin_chai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 380,
        "code": 3532,
        "name": "Xã Pu Sam Cáp",
        "codename": "xa_pu_sam_cap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 381,
        "code": 3538,
        "name": "Xã Nậm Mạ",
        "codename": "xa_nam_ma",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 382,
        "code": 3544,
        "name": "Xã Nậm Cuổi",
        "codename": "xa_nam_cuoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 383,
        "code": 3549,
        "name": "Xã Phong Thổ",
        "codename": "xa_phong_tho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 384,
        "code": 3562,
        "name": "Xã Sì Lở Lầu",
        "codename": "xa_si_lo_lau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 385,
        "code": 3571,
        "name": "Xã Dào San",
        "codename": "xa_dao_san",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 386,
        "code": 3583,
        "name": "Xã Khổng Lào",
        "codename": "xa_khong_lao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 387,
        "code": 3595,
        "name": "Xã Than Uyên",
        "codename": "xa_than_uyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 388,
        "code": 3598,
        "name": "Xã Tân Uyên",
        "codename": "xa_tan_uyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 389,
        "code": 3601,
        "name": "Xã Mường Khoa",
        "codename": "xa_muong_khoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 390,
        "code": 3613,
        "name": "Xã Nậm Sỏ",
        "codename": "xa_nam_so",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 391,
        "code": 3616,
        "name": "Xã Pắc Ta",
        "codename": "xa_pac_ta",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 392,
        "code": 3618,
        "name": "Xã Mường Than",
        "codename": "xa_muong_than",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 393,
        "code": 3637,
        "name": "Xã Mường Kim",
        "codename": "xa_muong_kim",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 394,
        "code": 3640,
        "name": "Xã Khoen On",
        "codename": "xa_khoen_on",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 12
    },
    {
        "id": 395,
        "code": 14,
        "name": "Tỉnh Sơn La",
        "codename": "son_la",
        "division_type": "tỉnh",
        "phone_code": 212,
        "parent_code": null
    },
    {
        "id": 396,
        "code": 3646,
        "name": "Phường Tô Hiệu",
        "codename": "phuong_to_hieu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 397,
        "code": 3664,
        "name": "Phường Chiềng An",
        "codename": "phuong_chieng_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 398,
        "code": 3670,
        "name": "Phường Chiềng Cơi",
        "codename": "phuong_chieng_coi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 399,
        "code": 3679,
        "name": "Phường Chiềng Sinh",
        "codename": "phuong_chieng_sinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 400,
        "code": 3688,
        "name": "Xã Mường Chiên",
        "codename": "xa_muong_chien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 401,
        "code": 3694,
        "name": "Xã Mường Giôn",
        "codename": "xa_muong_gion",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 402,
        "code": 3703,
        "name": "Xã Quỳnh Nhai",
        "codename": "xa_quynh_nhai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 403,
        "code": 3712,
        "name": "Xã Mường Sại",
        "codename": "xa_muong_sai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 404,
        "code": 3721,
        "name": "Xã Thuận Châu",
        "codename": "xa_thuan_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 405,
        "code": 3724,
        "name": "Xã Bình Thuận",
        "codename": "xa_binh_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 406,
        "code": 3727,
        "name": "Xã Mường É",
        "codename": "xa_muong_e",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 407,
        "code": 3754,
        "name": "Xã Chiềng La",
        "codename": "xa_chieng_la",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 408,
        "code": 3757,
        "name": "Xã Mường Khiêng",
        "codename": "xa_muong_khieng",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 409,
        "code": 3760,
        "name": "Xã Mường Bám",
        "codename": "xa_muong_bam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 410,
        "code": 3763,
        "name": "Xã Long Hẹ",
        "codename": "xa_long_he",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 411,
        "code": 3781,
        "name": "Xã Co Mạ",
        "codename": "xa_co_ma",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 412,
        "code": 3784,
        "name": "Xã Nậm Lầu",
        "codename": "xa_nam_lau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 413,
        "code": 3799,
        "name": "Xã Muổi Nọi",
        "codename": "xa_muoi_noi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 414,
        "code": 3808,
        "name": "Xã Mường La",
        "codename": "xa_muong_la",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 415,
        "code": 3814,
        "name": "Xã Chiềng Lao",
        "codename": "xa_chieng_lao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 416,
        "code": 3820,
        "name": "Xã Ngọc Chiến",
        "codename": "xa_ngoc_chien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 417,
        "code": 3847,
        "name": "Xã Mường Bú",
        "codename": "xa_muong_bu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 418,
        "code": 3850,
        "name": "Xã Chiềng Hoa",
        "codename": "xa_chieng_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 419,
        "code": 3856,
        "name": "Xã Bắc Yên",
        "codename": "xa_bac_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 420,
        "code": 3862,
        "name": "Xã Xím Vàng",
        "codename": "xa_xim_vang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 421,
        "code": 3868,
        "name": "Xã Tà Xùa",
        "codename": "xa_ta_xua",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 422,
        "code": 3871,
        "name": "Xã Pắc Ngà",
        "codename": "xa_pac_nga",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 423,
        "code": 3880,
        "name": "Xã Tạ Khoa",
        "codename": "xa_ta_khoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 424,
        "code": 3892,
        "name": "Xã Chiềng Sại",
        "codename": "xa_chieng_sai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 425,
        "code": 3901,
        "name": "Xã Suối Tọ",
        "codename": "xa_suoi_to",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 426,
        "code": 3907,
        "name": "Xã Mường Cơi",
        "codename": "xa_muong_coi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 427,
        "code": 3910,
        "name": "Xã Phù Yên",
        "codename": "xa_phu_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 428,
        "code": 3922,
        "name": "Xã Gia Phù",
        "codename": "xa_gia_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 429,
        "code": 3943,
        "name": "Xã Mường Bang",
        "codename": "xa_muong_bang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 430,
        "code": 3958,
        "name": "Xã Tường Hạ",
        "codename": "xa_tuong_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 431,
        "code": 3961,
        "name": "Xã Kim Bon",
        "codename": "xa_kim_bon",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 432,
        "code": 3970,
        "name": "Xã Tân Phong",
        "codename": "xa_tan_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 433,
        "code": 3979,
        "name": "Phường Mộc Sơn",
        "codename": "phuong_moc_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 434,
        "code": 3980,
        "name": "Phường Mộc Châu",
        "codename": "phuong_moc_chau",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 435,
        "code": 3982,
        "name": "Phường Thảo Nguyên",
        "codename": "phuong_thao_nguyen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 436,
        "code": 3985,
        "name": "Xã Chiềng Sơn",
        "codename": "xa_chieng_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 437,
        "code": 3997,
        "name": "Xã Tân Yên",
        "codename": "xa_tan_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 438,
        "code": 4000,
        "name": "Xã Đoàn Kết",
        "codename": "xa_doan_ket",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 439,
        "code": 4006,
        "name": "Xã Song Khủa",
        "codename": "xa_song_khua",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 440,
        "code": 4018,
        "name": "Xã Tô Múa",
        "codename": "xa_to_mua",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 441,
        "code": 4033,
        "name": "Phường Vân Sơn",
        "codename": "phuong_van_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 442,
        "code": 4045,
        "name": "Xã Lóng Sập",
        "codename": "xa_long_sap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 443,
        "code": 4048,
        "name": "Xã Vân Hồ",
        "codename": "xa_van_ho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 444,
        "code": 4057,
        "name": "Xã Xuân Nha",
        "codename": "xa_xuan_nha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 445,
        "code": 4075,
        "name": "Xã Yên Châu",
        "codename": "xa_yen_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 446,
        "code": 4078,
        "name": "Xã Chiềng Hặc",
        "codename": "xa_chieng_hac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 447,
        "code": 4087,
        "name": "Xã Yên Sơn",
        "codename": "xa_yen_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 448,
        "code": 4096,
        "name": "Xã Lóng Phiêng",
        "codename": "xa_long_phieng",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 449,
        "code": 4099,
        "name": "Xã Phiêng Khoài",
        "codename": "xa_phieng_khoai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 450,
        "code": 4105,
        "name": "Xã Mai Sơn",
        "codename": "xa_mai_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 451,
        "code": 4108,
        "name": "Xã Chiềng Sung",
        "codename": "xa_chieng_sung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 452,
        "code": 4117,
        "name": "Xã Mường Chanh",
        "codename": "xa_muong_chanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 453,
        "code": 4123,
        "name": "Xã Chiềng Mung",
        "codename": "xa_chieng_mung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 454,
        "code": 4132,
        "name": "Xã Chiềng Mai",
        "codename": "xa_chieng_mai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 455,
        "code": 4136,
        "name": "Xã Tà Hộc",
        "codename": "xa_ta_hoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 456,
        "code": 4144,
        "name": "Xã Phiêng Cằm",
        "codename": "xa_phieng_cam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 457,
        "code": 4159,
        "name": "Xã Phiêng Pằn",
        "codename": "xa_phieng_pan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 458,
        "code": 4168,
        "name": "Xã Sông Mã",
        "codename": "xa_song_ma",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 459,
        "code": 4171,
        "name": "Xã Bó Sinh",
        "codename": "xa_bo_sinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 460,
        "code": 4183,
        "name": "Xã Mường Lầm",
        "codename": "xa_muong_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 461,
        "code": 4186,
        "name": "Xã Nậm Ty",
        "codename": "xa_nam_ty",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 462,
        "code": 4195,
        "name": "Xã Chiềng Sơ",
        "codename": "xa_chieng_so",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 463,
        "code": 4204,
        "name": "Xã Chiềng Khoong",
        "codename": "xa_chieng_khoong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 464,
        "code": 4210,
        "name": "Xã Huổi Một",
        "codename": "xa_huoi_mot",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 465,
        "code": 4219,
        "name": "Xã Mường Hung",
        "codename": "xa_muong_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 466,
        "code": 4222,
        "name": "Xã Chiềng Khương",
        "codename": "xa_chieng_khuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 467,
        "code": 4228,
        "name": "Xã Púng Bánh",
        "codename": "xa_pung_banh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 468,
        "code": 4231,
        "name": "Xã Sốp Cộp",
        "codename": "xa_sop_cop",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 469,
        "code": 4240,
        "name": "Xã Mường Lèo",
        "codename": "xa_muong_leo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 470,
        "code": 4246,
        "name": "Xã Mường Lạn",
        "codename": "xa_muong_lan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 14
    },
    {
        "id": 471,
        "code": 15,
        "name": "Tỉnh Lào Cai",
        "codename": "lao_cai",
        "division_type": "tỉnh",
        "phone_code": 214,
        "parent_code": null
    },
    {
        "id": 472,
        "code": 2647,
        "name": "Phường Lào Cai",
        "codename": "phuong_lao_cai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 473,
        "code": 2671,
        "name": "Phường Cam Đường",
        "codename": "phuong_cam_duong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 474,
        "code": 2680,
        "name": "Xã Hợp Thành",
        "codename": "xa_hop_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 475,
        "code": 2683,
        "name": "Xã Bát Xát",
        "codename": "xa_bat_xat",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 476,
        "code": 2686,
        "name": "Xã A Mú Sung",
        "codename": "xa_a_mu_sung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 477,
        "code": 2695,
        "name": "Xã Trịnh Tường",
        "codename": "xa_trinh_tuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 478,
        "code": 2701,
        "name": "Xã Y Tý",
        "codename": "xa_y_ty",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 479,
        "code": 2707,
        "name": "Xã Dền Sáng",
        "codename": "xa_den_sang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 480,
        "code": 2725,
        "name": "Xã Bản Xèo",
        "codename": "xa_ban_xeo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 481,
        "code": 2728,
        "name": "Xã Mường Hum",
        "codename": "xa_muong_hum",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 482,
        "code": 2746,
        "name": "Xã Cốc San",
        "codename": "xa_coc_san",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 483,
        "code": 2752,
        "name": "Xã Pha Long",
        "codename": "xa_pha_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 484,
        "code": 2761,
        "name": "Xã Mường Khương",
        "codename": "xa_muong_khuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 485,
        "code": 2782,
        "name": "Xã Cao Sơn",
        "codename": "xa_cao_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 486,
        "code": 2788,
        "name": "Xã Bản Lầu",
        "codename": "xa_ban_lau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 487,
        "code": 2809,
        "name": "Xã Si Ma Cai",
        "codename": "xa_si_ma_cai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 488,
        "code": 2824,
        "name": "Xã Sín Chéng",
        "codename": "xa_sin_cheng",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 489,
        "code": 2839,
        "name": "Xã Bắc Hà",
        "codename": "xa_bac_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 490,
        "code": 2842,
        "name": "Xã Tả Củ Tỷ",
        "codename": "xa_ta_cu_ty",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 491,
        "code": 2848,
        "name": "Xã Lùng Phình",
        "codename": "xa_lung_phinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 492,
        "code": 2869,
        "name": "Xã Bản Liền",
        "codename": "xa_ban_lien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 493,
        "code": 2890,
        "name": "Xã Bảo Nhai",
        "codename": "xa_bao_nhai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 494,
        "code": 2896,
        "name": "Xã Cốc Lầu",
        "codename": "xa_coc_lau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 495,
        "code": 2902,
        "name": "Xã Phong Hải",
        "codename": "xa_phong_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 496,
        "code": 2905,
        "name": "Xã Bảo Thắng",
        "codename": "xa_bao_thang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 497,
        "code": 2908,
        "name": "Xã Tằng Loỏng",
        "codename": "xa_tang_loong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 498,
        "code": 2923,
        "name": "Xã Gia Phú",
        "codename": "xa_gia_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 499,
        "code": 2926,
        "name": "Xã Xuân Quang",
        "codename": "xa_xuan_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 500,
        "code": 2947,
        "name": "Xã Bảo Yên",
        "codename": "xa_bao_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 501,
        "code": 2953,
        "name": "Xã Nghĩa Đô",
        "codename": "xa_nghia_do",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 502,
        "code": 2962,
        "name": "Xã Xuân Hòa",
        "codename": "xa_xuan_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 503,
        "code": 2968,
        "name": "Xã Thượng Hà",
        "codename": "xa_thuong_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 504,
        "code": 2989,
        "name": "Xã Bảo Hà",
        "codename": "xa_bao_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 505,
        "code": 2998,
        "name": "Xã Phúc Khánh",
        "codename": "xa_phuc_khanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 506,
        "code": 3004,
        "name": "Xã Ngũ Chỉ Sơn",
        "codename": "xa_ngu_chi_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 507,
        "code": 3006,
        "name": "Phường Sa Pa",
        "codename": "phuong_sa_pa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 508,
        "code": 3013,
        "name": "Xã Tả Phìn",
        "codename": "xa_ta_phin",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 509,
        "code": 3037,
        "name": "Xã Tả Van",
        "codename": "xa_ta_van",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 510,
        "code": 3043,
        "name": "Xã Mường Bo",
        "codename": "xa_muong_bo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 511,
        "code": 3046,
        "name": "Xã Bản Hồ",
        "codename": "xa_ban_ho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 512,
        "code": 3061,
        "name": "Xã Võ Lao",
        "codename": "xa_vo_lao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 513,
        "code": 3076,
        "name": "Xã Nậm Chày",
        "codename": "xa_nam_chay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 514,
        "code": 3082,
        "name": "Xã Văn Bàn",
        "codename": "xa_van_ban",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 515,
        "code": 3085,
        "name": "Xã Nậm Xé",
        "codename": "xa_nam_xe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 516,
        "code": 3091,
        "name": "Xã Chiềng Ken",
        "codename": "xa_chieng_ken",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 517,
        "code": 3103,
        "name": "Xã Khánh Yên",
        "codename": "xa_khanh_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 518,
        "code": 3106,
        "name": "Xã Dương Quỳ",
        "codename": "xa_duong_quy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 519,
        "code": 3121,
        "name": "Xã Minh Lương",
        "codename": "xa_minh_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 520,
        "code": 4252,
        "name": "Phường Yên Bái",
        "codename": "phuong_yen_bai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 521,
        "code": 4273,
        "name": "Phường Nam Cường",
        "codename": "phuong_nam_cuong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 522,
        "code": 4279,
        "name": "Phường Văn Phú",
        "codename": "phuong_van_phu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 523,
        "code": 4288,
        "name": "Phường Nghĩa Lộ",
        "codename": "phuong_nghia_lo",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 524,
        "code": 4303,
        "name": "Xã Lục Yên",
        "codename": "xa_luc_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 525,
        "code": 4309,
        "name": "Xã Lâm Thượng",
        "codename": "xa_lam_thuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 526,
        "code": 4336,
        "name": "Xã Tân Lĩnh",
        "codename": "xa_tan_linh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 527,
        "code": 4342,
        "name": "Xã Khánh Hòa",
        "codename": "xa_khanh_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 528,
        "code": 4345,
        "name": "Xã Mường Lai",
        "codename": "xa_muong_lai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 529,
        "code": 4363,
        "name": "Xã Phúc Lợi",
        "codename": "xa_phuc_loi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 530,
        "code": 4375,
        "name": "Xã Mậu A",
        "codename": "xa_mau_a",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 531,
        "code": 4381,
        "name": "Xã Lâm Giang",
        "codename": "xa_lam_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 532,
        "code": 4387,
        "name": "Xã Châu Quế",
        "codename": "xa_chau_que",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 533,
        "code": 4399,
        "name": "Xã Đông Cuông",
        "codename": "xa_dong_cuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 534,
        "code": 4402,
        "name": "Xã Phong Dụ Hạ",
        "codename": "xa_phong_du_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 535,
        "code": 4423,
        "name": "Xã Phong Dụ Thượng",
        "codename": "xa_phong_du_thuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 536,
        "code": 4429,
        "name": "Xã Tân Hợp",
        "codename": "xa_tan_hop",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 537,
        "code": 4441,
        "name": "Xã Xuân Ái",
        "codename": "xa_xuan_ai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 538,
        "code": 4450,
        "name": "Xã Mỏ Vàng",
        "codename": "xa_mo_vang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 539,
        "code": 4456,
        "name": "Xã Mù Cang Chải",
        "codename": "xa_mu_cang_chai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 540,
        "code": 4462,
        "name": "Xã Nậm Có",
        "codename": "xa_nam_co",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 541,
        "code": 4465,
        "name": "Xã Khao Mang",
        "codename": "xa_khao_mang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 542,
        "code": 4474,
        "name": "Xã Lao Chải",
        "codename": "xa_lao_chai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 543,
        "code": 4489,
        "name": "Xã Chế Tạo",
        "codename": "xa_che_tao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 544,
        "code": 4492,
        "name": "Xã Púng Luông",
        "codename": "xa_pung_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 545,
        "code": 4498,
        "name": "Xã Trấn Yên",
        "codename": "xa_tran_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 546,
        "code": 4531,
        "name": "Xã Quy Mông",
        "codename": "xa_quy_mong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 547,
        "code": 4537,
        "name": "Xã Lương Thịnh",
        "codename": "xa_luong_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 548,
        "code": 4543,
        "name": "Phường Âu Lâu",
        "codename": "phuong_au_lau",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 549,
        "code": 4564,
        "name": "Xã Việt Hồng",
        "codename": "xa_viet_hong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 550,
        "code": 4576,
        "name": "Xã Hưng Khánh",
        "codename": "xa_hung_khanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 551,
        "code": 4585,
        "name": "Xã Hạnh Phúc",
        "codename": "xa_hanh_phuc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 552,
        "code": 4603,
        "name": "Xã Tà Xi Láng",
        "codename": "xa_ta_xi_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 553,
        "code": 4606,
        "name": "Xã Trạm Tấu",
        "codename": "xa_tram_tau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 554,
        "code": 4609,
        "name": "Xã Phình Hồ",
        "codename": "xa_phinh_ho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 555,
        "code": 4630,
        "name": "Xã Tú Lệ",
        "codename": "xa_tu_le",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 556,
        "code": 4636,
        "name": "Xã Gia Hội",
        "codename": "xa_gia_hoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 557,
        "code": 4651,
        "name": "Xã Sơn Lương",
        "codename": "xa_son_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 558,
        "code": 4660,
        "name": "Xã Liên Sơn",
        "codename": "xa_lien_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 559,
        "code": 4663,
        "name": "Phường Trung Tâm",
        "codename": "phuong_trung_tam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 560,
        "code": 4672,
        "name": "Xã Văn Chấn",
        "codename": "xa_van_chan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 561,
        "code": 4681,
        "name": "Phường Cầu Thia",
        "codename": "phuong_cau_thia",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 562,
        "code": 4693,
        "name": "Xã Cát Thịnh",
        "codename": "xa_cat_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 563,
        "code": 4699,
        "name": "Xã Chấn Thịnh",
        "codename": "xa_chan_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 564,
        "code": 4705,
        "name": "Xã Thượng Bằng La",
        "codename": "xa_thuong_bang_la",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 565,
        "code": 4711,
        "name": "Xã Nghĩa Tâm",
        "codename": "xa_nghia_tam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 566,
        "code": 4714,
        "name": "Xã Yên Bình",
        "codename": "xa_yen_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 567,
        "code": 4717,
        "name": "Xã Thác Bà",
        "codename": "xa_thac_ba",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 568,
        "code": 4726,
        "name": "Xã Cảm Nhân",
        "codename": "xa_cam_nhan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 569,
        "code": 4744,
        "name": "Xã Yên Thành",
        "codename": "xa_yen_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 570,
        "code": 4750,
        "name": "Xã Bảo Ái",
        "codename": "xa_bao_ai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 15
    },
    {
        "id": 571,
        "code": 19,
        "name": "Tỉnh Thái Nguyên",
        "codename": "thai_nguyen",
        "division_type": "tỉnh",
        "phone_code": 208,
        "parent_code": null
    },
    {
        "id": 572,
        "code": 1840,
        "name": "Phường Đức Xuân",
        "codename": "phuong_duc_xuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 573,
        "code": 1843,
        "name": "Phường Bắc Kạn",
        "codename": "phuong_bac_kan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 574,
        "code": 1849,
        "name": "Xã Phong Quang",
        "codename": "xa_phong_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 575,
        "code": 1864,
        "name": "Xã Bằng Thành",
        "codename": "xa_bang_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 576,
        "code": 1879,
        "name": "Xã Cao Minh",
        "codename": "xa_cao_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 577,
        "code": 1882,
        "name": "Xã Nghiên Loan",
        "codename": "xa_nghien_loan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 578,
        "code": 1894,
        "name": "Xã Phúc Lộc",
        "codename": "xa_phuc_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 579,
        "code": 1906,
        "name": "Xã Ba Bể",
        "codename": "xa_ba_be",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 580,
        "code": 1912,
        "name": "Xã Chợ Rã",
        "codename": "xa_cho_ra",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 581,
        "code": 1921,
        "name": "Xã Thượng Minh",
        "codename": "xa_thuong_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 582,
        "code": 1933,
        "name": "Xã Đồng Phúc",
        "codename": "xa_dong_phuc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 583,
        "code": 1936,
        "name": "Xã Nà Phặc",
        "codename": "xa_na_phac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 584,
        "code": 1942,
        "name": "Xã Bằng Vân",
        "codename": "xa_bang_van",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 585,
        "code": 1954,
        "name": "Xã Ngân Sơn",
        "codename": "xa_ngan_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 586,
        "code": 1957,
        "name": "Xã Thượng Quan",
        "codename": "xa_thuong_quan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 587,
        "code": 1960,
        "name": "Xã Hiệp Lực",
        "codename": "xa_hiep_luc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 588,
        "code": 1969,
        "name": "Xã Phủ Thông",
        "codename": "xa_phu_thong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 589,
        "code": 1981,
        "name": "Xã Vĩnh Thông",
        "codename": "xa_vinh_thong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 590,
        "code": 2008,
        "name": "Xã Cẩm Giàng",
        "codename": "xa_cam_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 591,
        "code": 2014,
        "name": "Xã Bạch Thông",
        "codename": "xa_bach_thong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 592,
        "code": 2020,
        "name": "Xã Chợ Đồn",
        "codename": "xa_cho_don",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 593,
        "code": 2026,
        "name": "Xã Nam Cường",
        "codename": "xa_nam_cuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 594,
        "code": 2038,
        "name": "Xã Quảng Bạch",
        "codename": "xa_quang_bach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 595,
        "code": 2044,
        "name": "Xã Yên Thịnh",
        "codename": "xa_yen_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 596,
        "code": 2071,
        "name": "Xã Nghĩa Tá",
        "codename": "xa_nghia_ta",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 597,
        "code": 2083,
        "name": "Xã Yên Phong",
        "codename": "xa_yen_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 598,
        "code": 2086,
        "name": "Xã Chợ Mới",
        "codename": "xa_cho_moi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 599,
        "code": 2101,
        "name": "Xã Thanh Mai",
        "codename": "xa_thanh_mai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 600,
        "code": 2104,
        "name": "Xã Tân Kỳ",
        "codename": "xa_tan_ky",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 601,
        "code": 2107,
        "name": "Xã Thanh Thịnh",
        "codename": "xa_thanh_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 602,
        "code": 2116,
        "name": "Xã Yên Bình",
        "codename": "xa_yen_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 603,
        "code": 2143,
        "name": "Xã Văn Lang",
        "codename": "xa_van_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 604,
        "code": 2152,
        "name": "Xã Cường Lợi",
        "codename": "xa_cuong_loi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 605,
        "code": 2155,
        "name": "Xã Na Rì",
        "codename": "xa_na_ri",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 606,
        "code": 2176,
        "name": "Xã Trần Phú",
        "codename": "xa_tran_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 607,
        "code": 2185,
        "name": "Xã Côn Minh",
        "codename": "xa_con_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 608,
        "code": 2191,
        "name": "Xã Xuân Dương",
        "codename": "xa_xuan_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 609,
        "code": 5443,
        "name": "Phường Phan Đình Phùng",
        "codename": "phuong_phan_dinh_phung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 610,
        "code": 5455,
        "name": "Phường Quyết Thắng",
        "codename": "phuong_quyet_thang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 611,
        "code": 5467,
        "name": "Phường Gia Sàng",
        "codename": "phuong_gia_sang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 612,
        "code": 5482,
        "name": "Phường Quan Triều",
        "codename": "phuong_quan_trieu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 613,
        "code": 5488,
        "name": "Xã Đại Phúc",
        "codename": "xa_dai_phuc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 614,
        "code": 5500,
        "name": "Phường Tích Lương",
        "codename": "phuong_tich_luong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 615,
        "code": 5503,
        "name": "Xã Tân Cương",
        "codename": "xa_tan_cuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 616,
        "code": 5518,
        "name": "Phường Sông Công",
        "codename": "phuong_song_cong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 617,
        "code": 5528,
        "name": "Phường Bách Quang",
        "codename": "phuong_bach_quang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 618,
        "code": 5533,
        "name": "Phường Bá Xuyên",
        "codename": "phuong_ba_xuyen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 619,
        "code": 5542,
        "name": "Xã Lam Vỹ",
        "codename": "xa_lam_vy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 620,
        "code": 5551,
        "name": "Xã Kim Phượng",
        "codename": "xa_kim_phuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 621,
        "code": 5563,
        "name": "Xã Phượng Tiến",
        "codename": "xa_phuong_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 622,
        "code": 5569,
        "name": "Xã Định Hóa",
        "codename": "xa_dinh_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 623,
        "code": 5581,
        "name": "Xã Trung Hội",
        "codename": "xa_trung_hoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 624,
        "code": 5587,
        "name": "Xã Bình Yên",
        "codename": "xa_binh_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 625,
        "code": 5602,
        "name": "Xã Phú Đình",
        "codename": "xa_phu_dinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 626,
        "code": 5605,
        "name": "Xã Bình Thành",
        "codename": "xa_binh_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 627,
        "code": 5611,
        "name": "Xã Phú Lương",
        "codename": "xa_phu_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 628,
        "code": 5620,
        "name": "Xã Yên Trạch",
        "codename": "xa_yen_trach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 629,
        "code": 5632,
        "name": "Xã Hợp Thành",
        "codename": "xa_hop_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 630,
        "code": 5641,
        "name": "Xã Vô Tranh",
        "codename": "xa_vo_tranh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 631,
        "code": 5662,
        "name": "Xã Trại Cau",
        "codename": "xa_trai_cau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 632,
        "code": 5665,
        "name": "Xã Văn Lăng",
        "codename": "xa_van_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 633,
        "code": 5674,
        "name": "Xã Quang Sơn",
        "codename": "xa_quang_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 634,
        "code": 5680,
        "name": "Xã Văn Hán",
        "codename": "xa_van_han",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 635,
        "code": 5692,
        "name": "Xã Đồng Hỷ",
        "codename": "xa_dong_hy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 636,
        "code": 5707,
        "name": "Xã Nam Hòa",
        "codename": "xa_nam_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 637,
        "code": 5710,
        "name": "Phường Linh Sơn",
        "codename": "phuong_linh_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 638,
        "code": 5716,
        "name": "Xã Võ Nhai",
        "codename": "xa_vo_nhai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 639,
        "code": 5719,
        "name": "Xã Sảng Mộc",
        "codename": "xa_sang_moc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 640,
        "code": 5722,
        "name": "Xã Nghinh Tường",
        "codename": "xa_nghinh_tuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 641,
        "code": 5725,
        "name": "Xã Thần Sa",
        "codename": "xa_than_sa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 642,
        "code": 5740,
        "name": "Xã La Hiên",
        "codename": "xa_la_hien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 643,
        "code": 5746,
        "name": "Xã Tràng Xá",
        "codename": "xa_trang_xa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 644,
        "code": 5755,
        "name": "Xã Dân Tiến",
        "codename": "xa_dan_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 645,
        "code": 5773,
        "name": "Xã Phú Xuyên",
        "codename": "xa_phu_xuyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 646,
        "code": 5776,
        "name": "Xã Đức Lương",
        "codename": "xa_duc_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 647,
        "code": 5788,
        "name": "Xã Phú Lạc",
        "codename": "xa_phu_lac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 648,
        "code": 5800,
        "name": "Xã Phú Thịnh",
        "codename": "xa_phu_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 649,
        "code": 5809,
        "name": "Xã An Khánh",
        "codename": "xa_an_khanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 650,
        "code": 5818,
        "name": "Xã La Bằng",
        "codename": "xa_la_bang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 651,
        "code": 5830,
        "name": "Xã Đại Từ",
        "codename": "xa_dai_tu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 652,
        "code": 5845,
        "name": "Xã Vạn Phú",
        "codename": "xa_van_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 653,
        "code": 5851,
        "name": "Xã Quân Chu",
        "codename": "xa_quan_chu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 654,
        "code": 5857,
        "name": "Phường Phúc Thuận",
        "codename": "phuong_phuc_thuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 655,
        "code": 5860,
        "name": "Phường Phổ Yên",
        "codename": "phuong_pho_yen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 656,
        "code": 5881,
        "name": "Xã Thành Công",
        "codename": "xa_thanh_cong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 657,
        "code": 5890,
        "name": "Phường Vạn Xuân",
        "codename": "phuong_van_xuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 658,
        "code": 5899,
        "name": "Phường Trung Thành",
        "codename": "phuong_trung_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 659,
        "code": 5908,
        "name": "Xã Phú Bình",
        "codename": "xa_phu_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 660,
        "code": 5917,
        "name": "Xã Tân Khánh",
        "codename": "xa_tan_khanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 661,
        "code": 5923,
        "name": "Xã Tân Thành",
        "codename": "xa_tan_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 662,
        "code": 5941,
        "name": "Xã Điềm Thụy",
        "codename": "xa_diem_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 663,
        "code": 5953,
        "name": "Xã Kha Sơn",
        "codename": "xa_kha_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 19
    },
    {
        "id": 664,
        "code": 20,
        "name": "Tỉnh Lạng Sơn",
        "codename": "lang_son",
        "division_type": "tỉnh",
        "phone_code": 205,
        "parent_code": null
    },
    {
        "id": 665,
        "code": 5977,
        "name": "Phường Đông Kinh",
        "codename": "phuong_dong_kinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 666,
        "code": 5983,
        "name": "Phường Lương Văn Tri",
        "codename": "phuong_luong_van_tri",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 667,
        "code": 5986,
        "name": "Phường Tam Thanh",
        "codename": "phuong_tam_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 668,
        "code": 6001,
        "name": "Xã Đoàn Kết",
        "codename": "xa_doan_ket",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 669,
        "code": 6004,
        "name": "Xã Quốc Khánh",
        "codename": "xa_quoc_khanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 670,
        "code": 6019,
        "name": "Xã Tân Tiến",
        "codename": "xa_tan_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 671,
        "code": 6037,
        "name": "Xã Kháng Chiến",
        "codename": "xa_khang_chien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 672,
        "code": 6040,
        "name": "Xã Thất Khê",
        "codename": "xa_that_khe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 673,
        "code": 6046,
        "name": "Xã Tràng Định",
        "codename": "xa_trang_dinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 674,
        "code": 6058,
        "name": "Xã Quốc Việt",
        "codename": "xa_quoc_viet",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 675,
        "code": 6073,
        "name": "Xã Hoa Thám",
        "codename": "xa_hoa_tham",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 676,
        "code": 6076,
        "name": "Xã Quý Hòa",
        "codename": "xa_quy_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 677,
        "code": 6079,
        "name": "Xã Hồng Phong",
        "codename": "xa_hong_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 678,
        "code": 6085,
        "name": "Xã Thiện Hòa",
        "codename": "xa_thien_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 679,
        "code": 6091,
        "name": "Xã Thiện Thuật",
        "codename": "xa_thien_thuat",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 680,
        "code": 6103,
        "name": "Xã Thiện Long",
        "codename": "xa_thien_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 681,
        "code": 6112,
        "name": "Xã Bình Gia",
        "codename": "xa_binh_gia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 682,
        "code": 6115,
        "name": "Xã Tân Văn",
        "codename": "xa_tan_van",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 683,
        "code": 6124,
        "name": "Xã Na Sầm",
        "codename": "xa_na_sam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 684,
        "code": 6148,
        "name": "Xã Thụy Hùng",
        "codename": "xa_thuy_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 685,
        "code": 6151,
        "name": "Xã Hội Hoan",
        "codename": "xa_hoi_hoan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 686,
        "code": 6154,
        "name": "Xã Văn Lãng",
        "codename": "xa_van_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 687,
        "code": 6172,
        "name": "Xã Hoàng Văn Thụ",
        "codename": "xa_hoang_van_thu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 688,
        "code": 6184,
        "name": "Xã Đồng Đăng",
        "codename": "xa_dong_dang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 689,
        "code": 6187,
        "name": "Phường Kỳ Lừa",
        "codename": "phuong_ky_lua",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 690,
        "code": 6196,
        "name": "Xã Ba Sơn",
        "codename": "xa_ba_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 691,
        "code": 6211,
        "name": "Xã Cao Lộc",
        "codename": "xa_cao_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 692,
        "code": 6220,
        "name": "Xã Công Sơn",
        "codename": "xa_cong_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 693,
        "code": 6253,
        "name": "Xã Văn Quan",
        "codename": "xa_van_quan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 694,
        "code": 6280,
        "name": "Xã Điềm He",
        "codename": "xa_diem_he",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 695,
        "code": 6286,
        "name": "Xã Khánh Khê",
        "codename": "xa_khanh_khe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 696,
        "code": 6298,
        "name": "Xã Yên Phúc",
        "codename": "xa_yen_phuc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 697,
        "code": 6313,
        "name": "Xã Tri Lễ",
        "codename": "xa_tri_le",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 698,
        "code": 6316,
        "name": "Xã Tân Đoàn",
        "codename": "xa_tan_doan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 699,
        "code": 6325,
        "name": "xã Bắc Sơn",
        "codename": "xa_bac_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 700,
        "code": 6337,
        "name": "Xã Tân Tri",
        "codename": "xa_tan_tri",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 701,
        "code": 6349,
        "name": "Xã Hưng Vũ",
        "codename": "xa_hung_vu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 702,
        "code": 6364,
        "name": "Xã Vũ Lễ",
        "codename": "xa_vu_le",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 703,
        "code": 6367,
        "name": "Xã Vũ Lăng",
        "codename": "xa_vu_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 704,
        "code": 6376,
        "name": "Xã Nhất Hòa",
        "codename": "xa_nhat_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 705,
        "code": 6385,
        "name": "Xã Hữu Lũng",
        "codename": "xa_huu_lung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 706,
        "code": 6391,
        "name": "Xã Yên Bình",
        "codename": "xa_yen_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 707,
        "code": 6400,
        "name": "Xã Hữu Liên",
        "codename": "xa_huu_lien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 708,
        "code": 6415,
        "name": "Xã Vân Nham",
        "codename": "xa_van_nham",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 709,
        "code": 6427,
        "name": "Xã Cai Kinh",
        "codename": "xa_cai_kinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 710,
        "code": 6436,
        "name": "Xã Thiện Tân",
        "codename": "xa_thien_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 711,
        "code": 6445,
        "name": "Xã Tân Thành",
        "codename": "xa_tan_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 712,
        "code": 6457,
        "name": "Xã Tuấn Sơn",
        "codename": "xa_tuan_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 713,
        "code": 6463,
        "name": "Xã Chi Lăng",
        "codename": "xa_chi_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 714,
        "code": 6475,
        "name": "Xã Bằng Mạc",
        "codename": "xa_bang_mac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 715,
        "code": 6481,
        "name": "Xã Chiến Thắng",
        "codename": "xa_chien_thang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 716,
        "code": 6496,
        "name": "Xã Nhân Lý",
        "codename": "xa_nhan_ly",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 717,
        "code": 6505,
        "name": "Xã Vạn Linh",
        "codename": "xa_van_linh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 718,
        "code": 6517,
        "name": "Xã Quan Sơn",
        "codename": "xa_quan_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 719,
        "code": 6526,
        "name": "Xã Na Dương",
        "codename": "xa_na_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 720,
        "code": 6529,
        "name": "Xã Lộc Bình",
        "codename": "xa_loc_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 721,
        "code": 6541,
        "name": "Xã Mẫu Sơn",
        "codename": "xa_mau_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 722,
        "code": 6565,
        "name": "Xã Khuất Xá",
        "codename": "xa_khuat_xa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 723,
        "code": 6577,
        "name": "Xã Thống Nhất",
        "codename": "xa_thong_nhat",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 724,
        "code": 6601,
        "name": "Xã Lợi Bác",
        "codename": "xa_loi_bac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 725,
        "code": 6607,
        "name": "Xã Xuân Dương",
        "codename": "xa_xuan_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 726,
        "code": 6613,
        "name": "Xã Đình Lập",
        "codename": "xa_dinh_lap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 727,
        "code": 6616,
        "name": "Xã Thái Bình",
        "codename": "xa_thai_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 728,
        "code": 6625,
        "name": "Xã Kiên Mộc",
        "codename": "xa_kien_moc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 729,
        "code": 6637,
        "name": "Xã Châu Sơn",
        "codename": "xa_chau_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 20
    },
    {
        "id": 730,
        "code": 22,
        "name": "Tỉnh Quảng Ninh",
        "codename": "quang_ninh",
        "division_type": "tỉnh",
        "phone_code": 203,
        "parent_code": null
    },
    {
        "id": 731,
        "code": 6652,
        "name": "Phường Hà Tu",
        "codename": "phuong_ha_tu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 732,
        "code": 6658,
        "name": "Phường Cao Xanh",
        "codename": "phuong_cao_xanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 733,
        "code": 6661,
        "name": "Phường Việt Hưng",
        "codename": "phuong_viet_hung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 734,
        "code": 6673,
        "name": "Phường Bãi Cháy",
        "codename": "phuong_bai_chay",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 735,
        "code": 6676,
        "name": "Phường Hà Lầm",
        "codename": "phuong_ha_lam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 736,
        "code": 6685,
        "name": "Phường Hồng Gai",
        "codename": "phuong_hong_gai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 737,
        "code": 6688,
        "name": "Phường Hạ Long",
        "codename": "phuong_ha_long",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 738,
        "code": 6706,
        "name": "Phường Tuần Châu",
        "codename": "phuong_tuan_chau",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 739,
        "code": 6709,
        "name": "Phường Móng Cái 2",
        "codename": "phuong_mong_cai_2",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 740,
        "code": 6712,
        "name": "Phường Móng Cái 1",
        "codename": "phuong_mong_cai_1",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 741,
        "code": 6724,
        "name": "Xã Hải Sơn",
        "codename": "xa_hai_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 742,
        "code": 6733,
        "name": "Xã Hải Ninh",
        "codename": "xa_hai_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 743,
        "code": 6736,
        "name": "Phường Móng Cái 3",
        "codename": "phuong_mong_cai_3",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 744,
        "code": 6757,
        "name": "Xã Vĩnh Thực",
        "codename": "xa_vinh_thuc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 745,
        "code": 6760,
        "name": "Phường Mông Dương",
        "codename": "phuong_mong_duong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 746,
        "code": 6778,
        "name": "Phường Quang Hanh",
        "codename": "phuong_quang_hanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 747,
        "code": 6781,
        "name": "Phường Cửa Ông",
        "codename": "phuong_cua_ong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 748,
        "code": 6793,
        "name": "Phường Cẩm Phả",
        "codename": "phuong_cam_pha",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 749,
        "code": 6799,
        "name": "Xã Hải Hòa",
        "codename": "xa_hai_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 750,
        "code": 6811,
        "name": "Phường Uông Bí",
        "codename": "phuong_uong_bi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 751,
        "code": 6820,
        "name": "Phường Vàng Danh",
        "codename": "phuong_vang_danh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 752,
        "code": 6832,
        "name": "Phường Yên Tử",
        "codename": "phuong_yen_tu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 753,
        "code": 6838,
        "name": "Xã Bình Liêu",
        "codename": "xa_binh_lieu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 754,
        "code": 6841,
        "name": "Xã Hoành Mô",
        "codename": "xa_hoanh_mo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 755,
        "code": 6856,
        "name": "Xã Lục Hồn",
        "codename": "xa_luc_hon",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 756,
        "code": 6862,
        "name": "Xã Tiên Yên",
        "codename": "xa_tien_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 757,
        "code": 6874,
        "name": "Xã Điền Xá",
        "codename": "xa_dien_xa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 758,
        "code": 6877,
        "name": "Xã Đông Ngũ",
        "codename": "xa_dong_ngu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 759,
        "code": 6886,
        "name": "Xã Hải Lạng",
        "codename": "xa_hai_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 760,
        "code": 6895,
        "name": "Xã Đầm Hà",
        "codename": "xa_dam_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 761,
        "code": 6913,
        "name": "Xã Quảng Tân",
        "codename": "xa_quang_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 762,
        "code": 6922,
        "name": "Xã Quảng Hà",
        "codename": "xa_quang_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 763,
        "code": 6931,
        "name": "Xã Quảng Đức",
        "codename": "xa_quang_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 764,
        "code": 6946,
        "name": "Xã Đường Hoa",
        "codename": "xa_duong_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 765,
        "code": 6967,
        "name": "Xã Cái Chiên",
        "codename": "xa_cai_chien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 766,
        "code": 6970,
        "name": "Xã Ba Chẽ",
        "codename": "xa_ba_che",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 767,
        "code": 6979,
        "name": "Xã Kỳ Thượng",
        "codename": "xa_ky_thuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 768,
        "code": 6985,
        "name": "Xã Lương Minh",
        "codename": "xa_luong_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 769,
        "code": 6994,
        "name": "Đặc khu Vân Đồn",
        "codename": "dac_khu_van_don",
        "division_type": "đặc khu",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 770,
        "code": 7030,
        "name": "Phường Hoành Bồ",
        "codename": "phuong_hoanh_bo",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 771,
        "code": 7054,
        "name": "Xã Quảng La",
        "codename": "xa_quang_la",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 772,
        "code": 7060,
        "name": "Xã Thống Nhất",
        "codename": "xa_thong_nhat",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 773,
        "code": 7069,
        "name": "Phường Mạo Khê",
        "codename": "phuong_mao_khe",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 774,
        "code": 7081,
        "name": "Phường Bình Khê",
        "codename": "phuong_binh_khe",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 775,
        "code": 7090,
        "name": "Phường An Sinh",
        "codename": "phuong_an_sinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 776,
        "code": 7093,
        "name": "Phường Đông Triều",
        "codename": "phuong_dong_trieu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 777,
        "code": 7114,
        "name": "Phường Hoàng Quế",
        "codename": "phuong_hoang_que",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 778,
        "code": 7132,
        "name": "Phường Quảng Yên",
        "codename": "phuong_quang_yen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 779,
        "code": 7135,
        "name": "Phường Đông Mai",
        "codename": "phuong_dong_mai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 780,
        "code": 7147,
        "name": "Phường Hiệp Hòa",
        "codename": "phuong_hiep_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 781,
        "code": 7168,
        "name": "Phường Hà An",
        "codename": "phuong_ha_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 782,
        "code": 7180,
        "name": "Phường Liên Hòa",
        "codename": "phuong_lien_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 783,
        "code": 7183,
        "name": "Phường Phong Cốc",
        "codename": "phuong_phong_coc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 784,
        "code": 7192,
        "name": "Đặc khu Cô Tô",
        "codename": "dac_khu_co_to",
        "division_type": "đặc khu",
        "phone_code": null,
        "parent_code": 22
    },
    {
        "id": 785,
        "code": 24,
        "name": "Tỉnh Bắc Ninh",
        "codename": "bac_ninh",
        "division_type": "tỉnh",
        "phone_code": 222,
        "parent_code": null
    },
    {
        "id": 786,
        "code": 7210,
        "name": "Phường Bắc Giang",
        "codename": "phuong_bac_giang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 787,
        "code": 7228,
        "name": "Phường Đa Mai",
        "codename": "phuong_da_mai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 788,
        "code": 7246,
        "name": "Xã Xuân Lương",
        "codename": "xa_xuan_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 789,
        "code": 7264,
        "name": "Xã Tam Tiến",
        "codename": "xa_tam_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 790,
        "code": 7282,
        "name": "Xã Đồng Kỳ",
        "codename": "xa_dong_ky",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 791,
        "code": 7288,
        "name": "Xã Yên Thế",
        "codename": "xa_yen_the",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 792,
        "code": 7294,
        "name": "Xã Bố Hạ",
        "codename": "xa_bo_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 793,
        "code": 7306,
        "name": "Xã Nhã Nam",
        "codename": "xa_nha_nam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 794,
        "code": 7330,
        "name": "Xã Phúc Hòa",
        "codename": "xa_phuc_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 795,
        "code": 7333,
        "name": "Xã Quang Trung",
        "codename": "xa_quang_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 796,
        "code": 7339,
        "name": "Xã Tân Yên",
        "codename": "xa_tan_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 797,
        "code": 7351,
        "name": "Xã Ngọc Thiện",
        "codename": "xa_ngoc_thien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 798,
        "code": 7375,
        "name": "Xã Lạng Giang",
        "codename": "xa_lang_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 799,
        "code": 7381,
        "name": "Xã Tiên Lục",
        "codename": "xa_tien_luc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 800,
        "code": 7399,
        "name": "Xã Kép",
        "codename": "xa_kep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 801,
        "code": 7420,
        "name": "Xã Mỹ Thái",
        "codename": "xa_my_thai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 802,
        "code": 7432,
        "name": "Xã Tân Dĩnh",
        "codename": "xa_tan_dinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 803,
        "code": 7444,
        "name": "Xã Lục Nam",
        "codename": "xa_luc_nam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 804,
        "code": 7450,
        "name": "Xã Đông Phú",
        "codename": "xa_dong_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 805,
        "code": 7462,
        "name": "Xã Bảo Đài",
        "codename": "xa_bao_dai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 806,
        "code": 7486,
        "name": "Xã Nghĩa Phương",
        "codename": "xa_nghia_phuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 807,
        "code": 7489,
        "name": "Xã Trường Sơn",
        "codename": "xa_truong_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 808,
        "code": 7492,
        "name": "Xã Lục Sơn",
        "codename": "xa_luc_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 809,
        "code": 7498,
        "name": "Xã Bắc Lũng",
        "codename": "xa_bac_lung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 810,
        "code": 7519,
        "name": "Xã Cẩm Lý",
        "codename": "xa_cam_ly",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 811,
        "code": 7525,
        "name": "Phường Chũ",
        "codename": "phuong_chu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 812,
        "code": 7531,
        "name": "Xã Tân Sơn",
        "codename": "xa_tan_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 813,
        "code": 7534,
        "name": "Xã Sa Lý",
        "codename": "xa_sa_ly",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 814,
        "code": 7537,
        "name": "Xã Biên Sơn",
        "codename": "xa_bien_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 815,
        "code": 7543,
        "name": "Xã Sơn Hải",
        "codename": "xa_son_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 816,
        "code": 7552,
        "name": "Xã Kiên Lao",
        "codename": "xa_kien_lao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 817,
        "code": 7573,
        "name": "Xã Biển Động",
        "codename": "xa_bien_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 818,
        "code": 7582,
        "name": "Xã Lục Ngạn",
        "codename": "xa_luc_ngan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 819,
        "code": 7594,
        "name": "Xã Đèo Gia",
        "codename": "xa_deo_gia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 820,
        "code": 7603,
        "name": "Xã Nam Dương",
        "codename": "xa_nam_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 821,
        "code": 7612,
        "name": "Phường Phượng Sơn",
        "codename": "phuong_phuong_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 822,
        "code": 7615,
        "name": "Xã Sơn Động",
        "codename": "xa_son_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 823,
        "code": 7616,
        "name": "Xã Tây Yên Tử",
        "codename": "xa_tay_yen_tu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 824,
        "code": 7621,
        "name": "Xã Vân Sơn",
        "codename": "xa_van_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 825,
        "code": 7627,
        "name": "Xã Đại Sơn",
        "codename": "xa_dai_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 826,
        "code": 7642,
        "name": "Xã Yên Định",
        "codename": "xa_yen_dinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 827,
        "code": 7654,
        "name": "Xã An Lạc",
        "codename": "xa_an_lac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 828,
        "code": 7663,
        "name": "Xã Tuấn Đạo",
        "codename": "xa_tuan_dao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 829,
        "code": 7672,
        "name": "Xã Dương Hưu",
        "codename": "xa_duong_huu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 830,
        "code": 7681,
        "name": "Phường Yên Dũng",
        "codename": "phuong_yen_dung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 831,
        "code": 7682,
        "name": "Phường Tân An",
        "codename": "phuong_tan_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 832,
        "code": 7696,
        "name": "Phường Tiền Phong",
        "codename": "phuong_tien_phong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 833,
        "code": 7699,
        "name": "Phường Tân Tiến",
        "codename": "phuong_tan_tien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 834,
        "code": 7735,
        "name": "Xã Đồng Việt",
        "codename": "xa_dong_viet",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 835,
        "code": 7738,
        "name": "Phường Cảnh Thụy",
        "codename": "phuong_canh_thuy",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 836,
        "code": 7774,
        "name": "Phường Tự Lạn",
        "codename": "phuong_tu_lan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 837,
        "code": 7777,
        "name": "Phường Việt Yên",
        "codename": "phuong_viet_yen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 838,
        "code": 7795,
        "name": "Phường Nếnh",
        "codename": "phuong_nenh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 839,
        "code": 7798,
        "name": "Phường Vân Hà",
        "codename": "phuong_van_ha",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 840,
        "code": 7822,
        "name": "Xã Hoàng Vân",
        "codename": "xa_hoang_van",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 841,
        "code": 7840,
        "name": "Xã Hiệp Hoà",
        "codename": "xa_hiep_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 842,
        "code": 7864,
        "name": "Xã Hợp Thịnh",
        "codename": "xa_hop_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 843,
        "code": 7870,
        "name": "Xã Xuân Cẩm",
        "codename": "xa_xuan_cam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 844,
        "code": 9169,
        "name": "Phường Vũ Ninh",
        "codename": "phuong_vu_ninh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 845,
        "code": 9187,
        "name": "Phường Kinh Bắc",
        "codename": "phuong_kinh_bac",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 846,
        "code": 9190,
        "name": "Phường Võ Cường",
        "codename": "phuong_vo_cuong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 847,
        "code": 9193,
        "name": "Xã Yên Phong",
        "codename": "xa_yen_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 848,
        "code": 9202,
        "name": "Xã Tam Giang",
        "codename": "xa_tam_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 849,
        "code": 9205,
        "name": "Xã Yên Trung",
        "codename": "xa_yen_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 850,
        "code": 9208,
        "name": "Xã Tam Đa",
        "codename": "xa_tam_da",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 851,
        "code": 9238,
        "name": "Xã Văn Môn",
        "codename": "xa_van_mon",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 852,
        "code": 9247,
        "name": "Phường Quế Võ",
        "codename": "phuong_que_vo",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 853,
        "code": 9253,
        "name": "Phường Nhân Hòa",
        "codename": "phuong_nhan_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 854,
        "code": 9265,
        "name": "Phường Phương Liễu",
        "codename": "phuong_phuong_lieu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 855,
        "code": 9286,
        "name": "Phường Nam Sơn",
        "codename": "phuong_nam_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 856,
        "code": 9292,
        "name": "Xã Phù Lãng",
        "codename": "xa_phu_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 857,
        "code": 9295,
        "name": "Phường Bồng Lai",
        "codename": "phuong_bong_lai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 858,
        "code": 9301,
        "name": "Phường Đào Viên",
        "codename": "phuong_dao_vien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 859,
        "code": 9313,
        "name": "Xã Chi Lăng",
        "codename": "xa_chi_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 860,
        "code": 9319,
        "name": "Xã Tiên Du",
        "codename": "xa_tien_du",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 861,
        "code": 9325,
        "name": "Phường Hạp Lĩnh",
        "codename": "phuong_hap_linh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 862,
        "code": 9334,
        "name": "Xã Liên Bão",
        "codename": "xa_lien_bao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 863,
        "code": 9340,
        "name": "Xã Đại Đồng",
        "codename": "xa_dai_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 864,
        "code": 9343,
        "name": "Xã Tân Chi",
        "codename": "xa_tan_chi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 865,
        "code": 9349,
        "name": "Xã Phật Tích",
        "codename": "xa_phat_tich",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 866,
        "code": 9367,
        "name": "Phường Từ Sơn",
        "codename": "phuong_tu_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 867,
        "code": 9370,
        "name": "Phường Tam Sơn",
        "codename": "phuong_tam_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 868,
        "code": 9379,
        "name": "Phường Phù Khê",
        "codename": "phuong_phu_khe",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 869,
        "code": 9385,
        "name": "Phường Đồng Nguyên",
        "codename": "phuong_dong_nguyen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 870,
        "code": 9400,
        "name": "Phường Thuận Thành",
        "codename": "phuong_thuan_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 871,
        "code": 9409,
        "name": "Phường Mão Điền",
        "codename": "phuong_mao_dien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 872,
        "code": 9427,
        "name": "Phường Trí Quả",
        "codename": "phuong_tri_qua",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 873,
        "code": 9430,
        "name": "Phường Trạm Lộ",
        "codename": "phuong_tram_lo",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 874,
        "code": 9433,
        "name": "Phường Song Liễu",
        "codename": "phuong_song_lieu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 875,
        "code": 9445,
        "name": "Phường Ninh Xá",
        "codename": "phuong_ninh_xa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 876,
        "code": 9454,
        "name": "Xã Gia Bình",
        "codename": "xa_gia_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 877,
        "code": 9466,
        "name": "Xã Cao Đức",
        "codename": "xa_cao_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 878,
        "code": 9469,
        "name": "Xã Đại Lai",
        "codename": "xa_dai_lai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 879,
        "code": 9475,
        "name": "Xã Nhân Thắng",
        "codename": "xa_nhan_thang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 880,
        "code": 9487,
        "name": "Xã Đông Cứu",
        "codename": "xa_dong_cuu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 881,
        "code": 9496,
        "name": "Xã Lương Tài",
        "codename": "xa_luong_tai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 882,
        "code": 9499,
        "name": "Xã Trung Kênh",
        "codename": "xa_trung_kenh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 883,
        "code": 9523,
        "name": "Xã Trung Chính",
        "codename": "xa_trung_chinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 884,
        "code": 9529,
        "name": "Xã Lâm Thao",
        "codename": "xa_lam_thao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 24
    },
    {
        "id": 886,
        "code": 4792,
        "name": "Phường Tân Hòa",
        "codename": "phuong_tan_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 887,
        "code": 4795,
        "name": "Phường Hòa Bình",
        "codename": "phuong_hoa_binh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 888,
        "code": 4828,
        "name": "Phường Thống Nhất",
        "codename": "phuong_thong_nhat",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 889,
        "code": 4831,
        "name": "Xã Đà Bắc",
        "codename": "xa_da_bac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 890,
        "code": 4846,
        "name": "Xã Đức Nhàn",
        "codename": "xa_duc_nhan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 891,
        "code": 4849,
        "name": "Xã Tân Pheo",
        "codename": "xa_tan_pheo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 892,
        "code": 4873,
        "name": "Xã Quy Đức",
        "codename": "xa_quy_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 893,
        "code": 4876,
        "name": "Xã Cao Sơn",
        "codename": "xa_cao_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 894,
        "code": 4891,
        "name": "Xã Tiền Phong",
        "codename": "xa_tien_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 895,
        "code": 4894,
        "name": "Phường Kỳ Sơn",
        "codename": "phuong_ky_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 896,
        "code": 4897,
        "name": "Xã Thịnh Minh",
        "codename": "xa_thinh_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 897,
        "code": 4924,
        "name": "Xã Lương Sơn",
        "codename": "xa_luong_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 898,
        "code": 4960,
        "name": "Xã Liên Sơn",
        "codename": "xa_lien_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 899,
        "code": 4978,
        "name": "Xã Kim Bôi",
        "codename": "xa_kim_boi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 900,
        "code": 4990,
        "name": "Xã Nật Sơn",
        "codename": "xa_nat_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 901,
        "code": 5014,
        "name": "Xã Mường Động",
        "codename": "xa_muong_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 902,
        "code": 5047,
        "name": "Xã Cao Dương",
        "codename": "xa_cao_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 903,
        "code": 5068,
        "name": "Xã Hợp Kim",
        "codename": "xa_hop_kim",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 904,
        "code": 5086,
        "name": "Xã Dũng Tiến",
        "codename": "xa_dung_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 905,
        "code": 5089,
        "name": "Xã Cao Phong",
        "codename": "xa_cao_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 906,
        "code": 5092,
        "name": "Xã Thung Nai",
        "codename": "xa_thung_nai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 907,
        "code": 5116,
        "name": "Xã Mường Thàng",
        "codename": "xa_muong_thang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 908,
        "code": 5128,
        "name": "Xã Tân Lạc",
        "codename": "xa_tan_lac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 909,
        "code": 5134,
        "name": "Xã Mường Hoa",
        "codename": "xa_muong_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 910,
        "code": 5152,
        "name": "Xã Vân Sơn",
        "codename": "xa_van_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 911,
        "code": 5158,
        "name": "Xã Mường Bi",
        "codename": "xa_muong_bi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 912,
        "code": 5191,
        "name": "Xã Toàn Thắng",
        "codename": "xa_toan_thang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 913,
        "code": 5200,
        "name": "Xã Mai Châu",
        "codename": "xa_mai_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 914,
        "code": 5206,
        "name": "Xã Tân Mai",
        "codename": "xa_tan_mai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 915,
        "code": 5212,
        "name": "Xã Pà Cò",
        "codename": "xa_pa_co",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 916,
        "code": 5245,
        "name": "Xã Bao La",
        "codename": "xa_bao_la",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 917,
        "code": 5251,
        "name": "Xã Mai Hạ",
        "codename": "xa_mai_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 918,
        "code": 5266,
        "name": "Xã Lạc Sơn",
        "codename": "xa_lac_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 919,
        "code": 5287,
        "name": "Xã Mường Vang",
        "codename": "xa_muong_vang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 920,
        "code": 5290,
        "name": "Xã Nhân Nghĩa",
        "codename": "xa_nhan_nghia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 921,
        "code": 5293,
        "name": "Xã Thượng Cốc",
        "codename": "xa_thuong_coc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 922,
        "code": 5305,
        "name": "Xã Yên Phú",
        "codename": "xa_yen_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 923,
        "code": 5323,
        "name": "Xã Quyết Thắng",
        "codename": "xa_quyet_thang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 924,
        "code": 5329,
        "name": "Xã Ngọc Sơn",
        "codename": "xa_ngoc_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 925,
        "code": 5347,
        "name": "Xã Đại Đồng",
        "codename": "xa_dai_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 926,
        "code": 5353,
        "name": "Xã Yên Thủy",
        "codename": "xa_yen_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 927,
        "code": 5362,
        "name": "Xã Lạc Lương",
        "codename": "xa_lac_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 928,
        "code": 5386,
        "name": "Xã Yên Trị",
        "codename": "xa_yen_tri",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 929,
        "code": 5392,
        "name": "Xã Lạc Thủy",
        "codename": "xa_lac_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 930,
        "code": 5395,
        "name": "Xã An Nghĩa",
        "codename": "xa_an_nghia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 931,
        "code": 5425,
        "name": "Xã An Bình",
        "codename": "xa_an_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 932,
        "code": 7894,
        "name": "Phường Nông Trang",
        "codename": "phuong_nong_trang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 933,
        "code": 7900,
        "name": "Phường Việt Trì",
        "codename": "phuong_viet_tri",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 934,
        "code": 7909,
        "name": "Phường Thanh Miếu",
        "codename": "phuong_thanh_mieu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 935,
        "code": 7918,
        "name": "Phường Vân Phú",
        "codename": "phuong_van_phu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 936,
        "code": 7942,
        "name": "Phường Phú Thọ",
        "codename": "phuong_phu_tho",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 937,
        "code": 7948,
        "name": "Phường Âu Cơ",
        "codename": "phuong_au_co",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 938,
        "code": 7954,
        "name": "Phường Phong Châu",
        "codename": "phuong_phong_chau",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 939,
        "code": 7969,
        "name": "Xã Đoan Hùng",
        "codename": "xa_doan_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 940,
        "code": 7996,
        "name": "Xã Bằng Luân",
        "codename": "xa_bang_luan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 941,
        "code": 7999,
        "name": "Xã Chí Đám",
        "codename": "xa_chi_dam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 942,
        "code": 8023,
        "name": "Xã Tây Cốc",
        "codename": "xa_tay_coc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 943,
        "code": 8038,
        "name": "Xã Chân Mộng",
        "codename": "xa_chan_mong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 944,
        "code": 8053,
        "name": "Xã Hạ Hòa",
        "codename": "xa_ha_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 945,
        "code": 8071,
        "name": "Xã Đan Thượng",
        "codename": "xa_dan_thuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 946,
        "code": 8110,
        "name": "Xã Hiền Lương",
        "codename": "xa_hien_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 947,
        "code": 8113,
        "name": "Xã Yên Kỳ",
        "codename": "xa_yen_ky",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 948,
        "code": 8134,
        "name": "Xã Văn Lang",
        "codename": "xa_van_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 949,
        "code": 8143,
        "name": "Xã Vĩnh Chân",
        "codename": "xa_vinh_chan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 950,
        "code": 8152,
        "name": "Xã Thanh Ba",
        "codename": "xa_thanh_ba",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 951,
        "code": 8173,
        "name": "Xã Quảng Yên",
        "codename": "xa_quang_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 952,
        "code": 8203,
        "name": "Xã Hoàng Cương",
        "codename": "xa_hoang_cuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 953,
        "code": 8209,
        "name": "Xã Đông Thành",
        "codename": "xa_dong_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 954,
        "code": 8218,
        "name": "Xã Chí Tiên",
        "codename": "xa_chi_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 955,
        "code": 8227,
        "name": "Xã Liên Minh",
        "codename": "xa_lien_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 956,
        "code": 8230,
        "name": "Xã Phù Ninh",
        "codename": "xa_phu_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 957,
        "code": 8236,
        "name": "Xã Phú Mỹ",
        "codename": "xa_phu_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 958,
        "code": 8245,
        "name": "Xã Trạm Thản",
        "codename": "xa_tram_than",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 959,
        "code": 8254,
        "name": "Xã Dân Chủ",
        "codename": "xa_dan_chu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 960,
        "code": 8275,
        "name": "Xã Bình Phú",
        "codename": "xa_binh_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 961,
        "code": 8290,
        "name": "Xã Yên Lập",
        "codename": "xa_yen_lap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 962,
        "code": 8296,
        "name": "Xã Sơn Lương",
        "codename": "xa_son_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 963,
        "code": 8305,
        "name": "Xã Xuân Viên",
        "codename": "xa_xuan_vien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 964,
        "code": 8311,
        "name": "Xã Trung Sơn",
        "codename": "xa_trung_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 965,
        "code": 8323,
        "name": "Xã Thượng Long",
        "codename": "xa_thuong_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 966,
        "code": 8338,
        "name": "Xã Minh Hòa",
        "codename": "xa_minh_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 967,
        "code": 8341,
        "name": "Xã Cẩm Khê",
        "codename": "xa_cam_khe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 968,
        "code": 8344,
        "name": "Xã Tiên Lương",
        "codename": "xa_tien_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 969,
        "code": 8377,
        "name": "Xã Vân Bán",
        "codename": "xa_van_ban",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 970,
        "code": 8398,
        "name": "Xã Phú Khê",
        "codename": "xa_phu_khe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 971,
        "code": 8416,
        "name": "Xã Hùng Việt",
        "codename": "xa_hung_viet",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 972,
        "code": 8431,
        "name": "Xã Đồng Lương",
        "codename": "xa_dong_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 973,
        "code": 8434,
        "name": "Xã Tam Nông",
        "codename": "xa_tam_nong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 974,
        "code": 8443,
        "name": "Xã Hiền Quan",
        "codename": "xa_hien_quan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 975,
        "code": 8467,
        "name": "Xã Vạn Xuân",
        "codename": "xa_van_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 976,
        "code": 8479,
        "name": "Xã Thọ Văn",
        "codename": "xa_tho_van",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 977,
        "code": 8494,
        "name": "Xã Lâm Thao",
        "codename": "xa_lam_thao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 978,
        "code": 8500,
        "name": "Xã Xuân Lũng",
        "codename": "xa_xuan_lung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 979,
        "code": 8515,
        "name": "Xã Hy Cương",
        "codename": "xa_hy_cuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 980,
        "code": 8521,
        "name": "Xã Phùng Nguyên",
        "codename": "xa_phung_nguyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 981,
        "code": 8527,
        "name": "Xã Bản Nguyên",
        "codename": "xa_ban_nguyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 982,
        "code": 8542,
        "name": "Xã Thanh Sơn",
        "codename": "xa_thanh_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 983,
        "code": 8545,
        "name": "Xã Thu Cúc",
        "codename": "xa_thu_cuc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 984,
        "code": 8560,
        "name": "Xã Lai Đồng",
        "codename": "xa_lai_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 985,
        "code": 8566,
        "name": "Xã Tân Sơn",
        "codename": "xa_tan_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 986,
        "code": 8584,
        "name": "Xã Võ Miếu",
        "codename": "xa_vo_mieu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 987,
        "code": 8590,
        "name": "Xã Xuân Đài",
        "codename": "xa_xuan_dai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 988,
        "code": 8593,
        "name": "Xã Minh Đài",
        "codename": "xa_minh_dai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 989,
        "code": 8611,
        "name": "Xã Văn Miếu",
        "codename": "xa_van_mieu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 990,
        "code": 8614,
        "name": "Xã Cự Đồng",
        "codename": "xa_cu_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 991,
        "code": 8620,
        "name": "Xã Long Cốc",
        "codename": "xa_long_coc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 992,
        "code": 8632,
        "name": "Xã Hương Cần",
        "codename": "xa_huong_can",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 993,
        "code": 8635,
        "name": "Xã Khả Cửu",
        "codename": "xa_kha_cuu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 994,
        "code": 8656,
        "name": "Xã Yên Sơn",
        "codename": "xa_yen_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 995,
        "code": 8662,
        "name": "Xã Đào Xá",
        "codename": "xa_dao_xa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 996,
        "code": 8674,
        "name": "Xã Thanh Thủy",
        "codename": "xa_thanh_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 997,
        "code": 8686,
        "name": "Xã Tu Vũ",
        "codename": "xa_tu_vu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 998,
        "code": 8707,
        "name": "Phường Vĩnh Yên",
        "codename": "phuong_vinh_yen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 999,
        "code": 8716,
        "name": "Phường Vĩnh Phúc",
        "codename": "phuong_vinh_phuc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1000,
        "code": 8740,
        "name": "Phường Phúc Yên",
        "codename": "phuong_phuc_yen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1001,
        "code": 8746,
        "name": "Phường Xuân Hòa",
        "codename": "phuong_xuan_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1002,
        "code": 8761,
        "name": "Xã Lập Thạch",
        "codename": "xa_lap_thach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1003,
        "code": 8770,
        "name": "Xã Hợp Lý",
        "codename": "xa_hop_ly",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1004,
        "code": 8773,
        "name": "Xã Yên Lãng",
        "codename": "xa_yen_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1005,
        "code": 8782,
        "name": "Xã Hải Lựu",
        "codename": "xa_hai_luu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1006,
        "code": 8788,
        "name": "Xã Thái Hòa",
        "codename": "xa_thai_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1007,
        "code": 8812,
        "name": "Xã Liên Hòa",
        "codename": "xa_lien_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1008,
        "code": 8824,
        "name": "Xã Tam Sơn",
        "codename": "xa_tam_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1009,
        "code": 8842,
        "name": "Xã Tiên Lữ",
        "codename": "xa_tien_lu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1010,
        "code": 8848,
        "name": "Xã Sông Lô",
        "codename": "xa_song_lo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1011,
        "code": 8866,
        "name": "Xã Sơn Đông",
        "codename": "xa_son_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1012,
        "code": 8869,
        "name": "Xã Tam Dương",
        "codename": "xa_tam_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1013,
        "code": 8872,
        "name": "Xã Tam Dương Bắc",
        "codename": "xa_tam_duong_bac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1014,
        "code": 8896,
        "name": "Xã Hoàng An",
        "codename": "xa_hoang_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1015,
        "code": 8905,
        "name": "Xã Hội Thịnh",
        "codename": "xa_hoi_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1016,
        "code": 8911,
        "name": "Xã Tam Đảo",
        "codename": "xa_tam_dao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1017,
        "code": 8914,
        "name": "Xã Đạo Trù",
        "codename": "xa_dao_tru",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1018,
        "code": 8923,
        "name": "Xã Đại Đình",
        "codename": "xa_dai_dinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1019,
        "code": 8935,
        "name": "Xã Bình Nguyên",
        "codename": "xa_binh_nguyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1020,
        "code": 8944,
        "name": "Xã Bình Tuyền",
        "codename": "xa_binh_tuyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1021,
        "code": 8950,
        "name": "Xã Bình Xuyên",
        "codename": "xa_binh_xuyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1022,
        "code": 8971,
        "name": "Xã Xuân Lãng",
        "codename": "xa_xuan_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1023,
        "code": 9025,
        "name": "Xã Yên Lạc",
        "codename": "xa_yen_lac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1024,
        "code": 9040,
        "name": "Xã Tề Lỗ",
        "codename": "xa_te_lo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1025,
        "code": 9043,
        "name": "Xã Tam Hồng",
        "codename": "xa_tam_hong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1026,
        "code": 9052,
        "name": "Xã Nguyệt Đức",
        "codename": "xa_nguyet_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1027,
        "code": 9064,
        "name": "Xã Liên Châu",
        "codename": "xa_lien_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1028,
        "code": 9076,
        "name": "Xã Vĩnh Tường",
        "codename": "xa_vinh_tuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1029,
        "code": 9079,
        "name": "Xã Vĩnh An",
        "codename": "xa_vinh_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1030,
        "code": 9100,
        "name": "Xã Vĩnh Hưng",
        "codename": "xa_vinh_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1031,
        "code": 9106,
        "name": "Xã Vĩnh Thành",
        "codename": "xa_vinh_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1032,
        "code": 9112,
        "name": "Xã Thổ Tang",
        "codename": "xa_tho_tang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1033,
        "code": 9154,
        "name": "Xã Vĩnh Phú",
        "codename": "xa_vinh_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 25
    },
    {
        "id": 1034,
        "code": 31,
        "name": "Thành phố Hải Phòng",
        "codename": "hai_phong",
        "division_type": "thành phố trung ương",
        "phone_code": 225,
        "parent_code": null
    },
    {
        "id": 1035,
        "code": 10507,
        "name": "Phường Thành Đông",
        "codename": "phuong_thanh_dong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1036,
        "code": 10525,
        "name": "Phường Hải Dương",
        "codename": "phuong_hai_duong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1037,
        "code": 10532,
        "name": "Phường Lê Thanh Nghị",
        "codename": "phuong_le_thanh_nghi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1038,
        "code": 10537,
        "name": "Phường Tân Hưng",
        "codename": "phuong_tan_hung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1039,
        "code": 10543,
        "name": "Phường Việt Hòa",
        "codename": "phuong_viet_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1040,
        "code": 10546,
        "name": "Phường Chí Linh",
        "codename": "phuong_chi_linh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1041,
        "code": 10549,
        "name": "Phường Chu Văn An",
        "codename": "phuong_chu_van_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1042,
        "code": 10552,
        "name": "Phường Nguyễn Trãi",
        "codename": "phuong_nguyen_trai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1043,
        "code": 10570,
        "name": "Phường Trần Hưng Đạo",
        "codename": "phuong_tran_hung_dao",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1044,
        "code": 10573,
        "name": "Phường Trần Nhân Tông",
        "codename": "phuong_tran_nhan_tong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1045,
        "code": 10603,
        "name": "Phường Lê Đại Hành",
        "codename": "phuong_le_dai_hanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1046,
        "code": 10606,
        "name": "Xã Nam Sách",
        "codename": "xa_nam_sach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1047,
        "code": 10615,
        "name": "Xã Hợp Tiến",
        "codename": "xa_hop_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1048,
        "code": 10633,
        "name": "Xã Trần Phú",
        "codename": "xa_tran_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1049,
        "code": 10642,
        "name": "Xã Thái Tân",
        "codename": "xa_thai_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1050,
        "code": 10645,
        "name": "Xã An Phú",
        "codename": "xa_an_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1051,
        "code": 10660,
        "name": "Phường Ái Quốc",
        "codename": "phuong_ai_quoc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1052,
        "code": 10675,
        "name": "Phường Kinh Môn",
        "codename": "phuong_kinh_mon",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1053,
        "code": 10678,
        "name": "Phường Bắc An Phụ",
        "codename": "phuong_bac_an_phu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1054,
        "code": 10705,
        "name": "Xã Nam An Phụ",
        "codename": "xa_nam_an_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1055,
        "code": 10714,
        "name": "Phường Nhị Chiểu",
        "codename": "phuong_nhi_chieu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1056,
        "code": 10726,
        "name": "Phường Phạm Sư Mạnh",
        "codename": "phuong_pham_su_manh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1057,
        "code": 10729,
        "name": "Phường Trần Liễu",
        "codename": "phuong_tran_lieu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1058,
        "code": 10744,
        "name": "Phường Nguyễn Đại Năng",
        "codename": "phuong_nguyen_dai_nang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1059,
        "code": 10750,
        "name": "Xã Phú Thái",
        "codename": "xa_phu_thai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1060,
        "code": 10756,
        "name": "Xã Lai Khê",
        "codename": "xa_lai_khe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1061,
        "code": 10792,
        "name": "Xã An Thành",
        "codename": "xa_an_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1062,
        "code": 10804,
        "name": "Xã Kim Thành",
        "codename": "xa_kim_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1063,
        "code": 10813,
        "name": "Xã Thanh Hà",
        "codename": "xa_thanh_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1064,
        "code": 10816,
        "name": "Xã Hà Bắc",
        "codename": "xa_ha_bac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1065,
        "code": 10837,
        "name": "Phường Nam Đồng",
        "codename": "phuong_nam_dong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1066,
        "code": 10843,
        "name": "Xã Hà Nam",
        "codename": "xa_ha_nam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1067,
        "code": 10846,
        "name": "Xã Hà Tây",
        "codename": "xa_ha_tay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1068,
        "code": 10882,
        "name": "Xã Hà Đông",
        "codename": "xa_ha_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1069,
        "code": 10888,
        "name": "Xã Cẩm Giang",
        "codename": "xa_cam_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1070,
        "code": 10891,
        "name": "Phường Tứ Minh",
        "codename": "phuong_tu_minh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1071,
        "code": 10903,
        "name": "Xã Cẩm Giàng",
        "codename": "xa_cam_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1072,
        "code": 10909,
        "name": "Xã Tuệ Tĩnh",
        "codename": "xa_tue_tinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1073,
        "code": 10930,
        "name": "Xã Mao Điền",
        "codename": "xa_mao_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1074,
        "code": 10945,
        "name": "Xã Kẻ Sặt",
        "codename": "xa_ke_sat",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1075,
        "code": 10966,
        "name": "Xã Bình Giang",
        "codename": "xa_binh_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1076,
        "code": 10972,
        "name": "Xã Đường An",
        "codename": "xa_duong_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1077,
        "code": 10993,
        "name": "Xã Thượng Hồng",
        "codename": "xa_thuong_hong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1078,
        "code": 10999,
        "name": "Xã Gia Lộc",
        "codename": "xa_gia_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1079,
        "code": 11002,
        "name": "Phường Thạch Khôi",
        "codename": "phuong_thach_khoi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1080,
        "code": 11020,
        "name": "Xã Yết Kiêu",
        "codename": "xa_yet_kieu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1081,
        "code": 11050,
        "name": "Xã Gia Phúc",
        "codename": "xa_gia_phuc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1082,
        "code": 11065,
        "name": "Xã Trường Tân",
        "codename": "xa_truong_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1083,
        "code": 11074,
        "name": "Xã Tứ Kỳ",
        "codename": "xa_tu_ky",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1084,
        "code": 11086,
        "name": "Xã Đại Sơn",
        "codename": "xa_dai_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1085,
        "code": 11113,
        "name": "Xã Tân Kỳ",
        "codename": "xa_tan_ky",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1086,
        "code": 11131,
        "name": "Xã Chí Minh",
        "codename": "xa_chi_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1087,
        "code": 11140,
        "name": "Xã Lạc Phượng",
        "codename": "xa_lac_phuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1088,
        "code": 11146,
        "name": "Xã Nguyên Giáp",
        "codename": "xa_nguyen_giap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1089,
        "code": 11164,
        "name": "Xã Vĩnh Lại",
        "codename": "xa_vinh_lai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1090,
        "code": 11167,
        "name": "Xã Tân An",
        "codename": "xa_tan_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1091,
        "code": 11203,
        "name": "Xã Ninh Giang",
        "codename": "xa_ninh_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1092,
        "code": 11218,
        "name": "Xã Hồng Châu",
        "codename": "xa_hong_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1093,
        "code": 11224,
        "name": "Xã Khúc Thừa Dụ",
        "codename": "xa_khuc_thua_du",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1094,
        "code": 11239,
        "name": "Xã Thanh Miện",
        "codename": "xa_thanh_mien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1095,
        "code": 11242,
        "name": "Xã Nguyễn Lương Bằng",
        "codename": "xa_nguyen_luong_bang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1096,
        "code": 11254,
        "name": "Xã Bắc Thanh Miện",
        "codename": "xa_bac_thanh_mien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1097,
        "code": 11257,
        "name": "Xã Hải Hưng",
        "codename": "xa_hai_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1098,
        "code": 11284,
        "name": "Xã Nam Thanh Miện",
        "codename": "xa_nam_thanh_mien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1099,
        "code": 11311,
        "name": "Phường Hồng Bàng",
        "codename": "phuong_hong_bang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1100,
        "code": 11329,
        "name": "Phường Ngô Quyền",
        "codename": "phuong_ngo_quyen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1101,
        "code": 11359,
        "name": "Phường Gia Viên",
        "codename": "phuong_gia_vien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1102,
        "code": 11383,
        "name": "Phường Lê Chân",
        "codename": "phuong_le_chan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1103,
        "code": 11407,
        "name": "Phường An Biên",
        "codename": "phuong_an_bien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1104,
        "code": 11411,
        "name": "Phường Đông Hải",
        "codename": "phuong_dong_hai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1105,
        "code": 11413,
        "name": "Phường Hải An",
        "codename": "phuong_hai_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1106,
        "code": 11443,
        "name": "Phường Kiến An",
        "codename": "phuong_kien_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1107,
        "code": 11446,
        "name": "Phường Phù Liễn",
        "codename": "phuong_phu_lien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1108,
        "code": 11455,
        "name": "Phường Đồ Sơn",
        "codename": "phuong_do_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1109,
        "code": 11473,
        "name": "Phường Bạch Đằng",
        "codename": "phuong_bach_dang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1110,
        "code": 11488,
        "name": "Phường Lưu Kiếm",
        "codename": "phuong_luu_kiem",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1111,
        "code": 11503,
        "name": "Xã Việt Khê",
        "codename": "xa_viet_khe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1112,
        "code": 11506,
        "name": "Phường Lê Ích Mộc",
        "codename": "phuong_le_ich_moc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1113,
        "code": 11533,
        "name": "Phường Hòa Bình",
        "codename": "phuong_hoa_binh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1114,
        "code": 11542,
        "name": "Phường Nam Triệu",
        "codename": "phuong_nam_trieu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1115,
        "code": 11557,
        "name": "Phường Thiên Hương",
        "codename": "phuong_thien_huong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1116,
        "code": 11560,
        "name": "Phường Thủy Nguyên",
        "codename": "phuong_thuy_nguyen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1117,
        "code": 11581,
        "name": "Phường An Dương",
        "codename": "phuong_an_duong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1118,
        "code": 11593,
        "name": "Phường An Phong",
        "codename": "phuong_an_phong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1119,
        "code": 11602,
        "name": "Phường Hồng An",
        "codename": "phuong_hong_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1120,
        "code": 11617,
        "name": "Phường An Hải",
        "codename": "phuong_an_hai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1121,
        "code": 11629,
        "name": "Xã An Lão",
        "codename": "xa_an_lao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1122,
        "code": 11635,
        "name": "Xã An Trường",
        "codename": "xa_an_truong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1123,
        "code": 11647,
        "name": "Xã An Quang",
        "codename": "xa_an_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1124,
        "code": 11668,
        "name": "Xã An Khánh",
        "codename": "xa_an_khanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1125,
        "code": 11674,
        "name": "Xã An Hưng",
        "codename": "xa_an_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1126,
        "code": 11680,
        "name": "Xã Kiến Thụy",
        "codename": "xa_kien_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1127,
        "code": 11689,
        "name": "Phường Hưng Đạo",
        "codename": "phuong_hung_dao",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1128,
        "code": 11692,
        "name": "Phường Dương Kinh",
        "codename": "phuong_duong_kinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1129,
        "code": 11713,
        "name": "Xã Nghi Dương",
        "codename": "xa_nghi_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1130,
        "code": 11725,
        "name": "Xã Kiến Minh",
        "codename": "xa_kien_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1131,
        "code": 11728,
        "name": "Xã Kiến Hưng",
        "codename": "xa_kien_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1132,
        "code": 11737,
        "name": "Phường Nam Đồ Sơn",
        "codename": "phuong_nam_do_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1133,
        "code": 11749,
        "name": "Xã Kiến Hải",
        "codename": "xa_kien_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1134,
        "code": 11755,
        "name": "Xã Tiên Lãng",
        "codename": "xa_tien_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1135,
        "code": 11761,
        "name": "Xã Quyết Thắng",
        "codename": "xa_quyet_thang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1136,
        "code": 11779,
        "name": "Xã Tân Minh",
        "codename": "xa_tan_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1137,
        "code": 11791,
        "name": "Xã Tiên Minh",
        "codename": "xa_tien_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1138,
        "code": 11806,
        "name": "Xã Chấn Hưng",
        "codename": "xa_chan_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1139,
        "code": 11809,
        "name": "Xã Hùng Thắng",
        "codename": "xa_hung_thang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1140,
        "code": 11824,
        "name": "Xã Vĩnh Bảo",
        "codename": "xa_vinh_bao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1141,
        "code": 11836,
        "name": "Xã Vĩnh Thịnh",
        "codename": "xa_vinh_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1142,
        "code": 11842,
        "name": "Xã Vĩnh Thuận",
        "codename": "xa_vinh_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1143,
        "code": 11848,
        "name": "Xã Vĩnh Hòa",
        "codename": "xa_vinh_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1144,
        "code": 11875,
        "name": "Xã Vĩnh Hải",
        "codename": "xa_vinh_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1145,
        "code": 11887,
        "name": "Xã Vĩnh Am",
        "codename": "xa_vinh_am",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1146,
        "code": 11911,
        "name": "Xã Nguyễn Bỉnh Khiêm",
        "codename": "xa_nguyen_binh_khiem",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1147,
        "code": 11914,
        "name": "Đặc khu Cát Hải",
        "codename": "dac_khu_cat_hai",
        "division_type": "đặc khu",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1148,
        "code": 11948,
        "name": "Đặc khu Bạch Long Vĩ",
        "codename": "dac_khu_bach_long_vi",
        "division_type": "đặc khu",
        "phone_code": null,
        "parent_code": 31
    },
    {
        "id": 1149,
        "code": 33,
        "name": "Tỉnh Hưng Yên",
        "codename": "hung_yen",
        "division_type": "tỉnh",
        "phone_code": 221,
        "parent_code": null
    },
    {
        "id": 1150,
        "code": 11953,
        "name": "Phường Phố Hiến",
        "codename": "phuong_pho_hien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1151,
        "code": 11977,
        "name": "Xã Tân Hưng",
        "codename": "xa_tan_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1152,
        "code": 11980,
        "name": "Phường Hồng Châu",
        "codename": "phuong_hong_chau",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1153,
        "code": 11983,
        "name": "Phường Sơn Nam",
        "codename": "phuong_son_nam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1154,
        "code": 11992,
        "name": "Xã Lạc Đạo",
        "codename": "xa_lac_dao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1155,
        "code": 11995,
        "name": "Xã Đại Đồng",
        "codename": "xa_dai_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1156,
        "code": 12004,
        "name": "Xã Như Quỳnh",
        "codename": "xa_nhu_quynh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1157,
        "code": 12019,
        "name": "Xã Văn Giang",
        "codename": "xa_van_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1158,
        "code": 12025,
        "name": "Xã Phụng Công",
        "codename": "xa_phung_cong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1159,
        "code": 12031,
        "name": "Xã Nghĩa Trụ",
        "codename": "xa_nghia_tru",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1160,
        "code": 12049,
        "name": "Xã Mễ Sở",
        "codename": "xa_me_so",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1161,
        "code": 12064,
        "name": "Xã Nguyễn Văn Linh",
        "codename": "xa_nguyen_van_linh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1162,
        "code": 12070,
        "name": "Xã Hoàn Long",
        "codename": "xa_hoan_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1163,
        "code": 12073,
        "name": "Xã Yên Mỹ",
        "codename": "xa_yen_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1164,
        "code": 12091,
        "name": "Xã Việt Yên",
        "codename": "xa_viet_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1165,
        "code": 12103,
        "name": "Phường Mỹ Hào",
        "codename": "phuong_my_hao",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1166,
        "code": 12127,
        "name": "Phường Thượng Hồng",
        "codename": "phuong_thuong_hong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1167,
        "code": 12133,
        "name": "Phường Đường Hào",
        "codename": "phuong_duong_hao",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1168,
        "code": 12142,
        "name": "Xã Ân Thi",
        "codename": "xa_an_thi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1169,
        "code": 12148,
        "name": "Xã Phạm Ngũ Lão",
        "codename": "xa_pham_ngu_lao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1170,
        "code": 12166,
        "name": "Xã Xuân Trúc",
        "codename": "xa_xuan_truc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1171,
        "code": 12184,
        "name": "Xã Nguyễn Trãi",
        "codename": "xa_nguyen_trai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1172,
        "code": 12196,
        "name": "Xã Hồng Quang",
        "codename": "xa_hong_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1173,
        "code": 12205,
        "name": "Xã Khoái Châu",
        "codename": "xa_khoai_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1174,
        "code": 12223,
        "name": "Xã Triệu Việt Vương",
        "codename": "xa_trieu_viet_vuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1175,
        "code": 12238,
        "name": "Xã Việt Tiến",
        "codename": "xa_viet_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1176,
        "code": 12247,
        "name": "Xã Châu Ninh",
        "codename": "xa_chau_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1177,
        "code": 12271,
        "name": "Xã Chí Minh",
        "codename": "xa_chi_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1178,
        "code": 12280,
        "name": "Xã Lương Bằng",
        "codename": "xa_luong_bang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1179,
        "code": 12286,
        "name": "Xã Nghĩa Dân",
        "codename": "xa_nghia_dan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1180,
        "code": 12313,
        "name": "Xã Đức Hợp",
        "codename": "xa_duc_hop",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1181,
        "code": 12322,
        "name": "Xã Hiệp Cường",
        "codename": "xa_hiep_cuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1182,
        "code": 12337,
        "name": "Xã Hoàng Hoa Thám",
        "codename": "xa_hoang_hoa_tham",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1183,
        "code": 12361,
        "name": "Xã Tiên Hoa",
        "codename": "xa_tien_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1184,
        "code": 12364,
        "name": "Xã Tiên Lữ",
        "codename": "xa_tien_lu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1185,
        "code": 12391,
        "name": "Xã Quang Hưng",
        "codename": "xa_quang_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1186,
        "code": 12406,
        "name": "Xã Đoàn Đào",
        "codename": "xa_doan_dao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1187,
        "code": 12424,
        "name": "Xã Tiên Tiến",
        "codename": "xa_tien_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1188,
        "code": 12427,
        "name": "Xã Tống Trân",
        "codename": "xa_tong_tran",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1189,
        "code": 12452,
        "name": "Phường Trần Hưng Đạo",
        "codename": "phuong_tran_hung_dao",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1190,
        "code": 12454,
        "name": "Phường Trần Lãm",
        "codename": "phuong_tran_lam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1191,
        "code": 12466,
        "name": "Phường Vũ Phúc",
        "codename": "phuong_vu_phuc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1192,
        "code": 12472,
        "name": "Xã Quỳnh Phụ",
        "codename": "xa_quynh_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1193,
        "code": 12499,
        "name": "Xã A Sào",
        "codename": "xa_a_sao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1194,
        "code": 12511,
        "name": "Xã Minh Thọ",
        "codename": "xa_minh_tho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1195,
        "code": 12517,
        "name": "Xã Ngọc Lâm",
        "codename": "xa_ngoc_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1196,
        "code": 12523,
        "name": "Xã Phụ Dực",
        "codename": "xa_phu_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1197,
        "code": 12526,
        "name": "Xã Đồng Bằng",
        "codename": "xa_dong_bang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1198,
        "code": 12532,
        "name": "Xã Nguyễn Du",
        "codename": "xa_nguyen_du",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1199,
        "code": 12577,
        "name": "Xã Quỳnh An",
        "codename": "xa_quynh_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1200,
        "code": 12583,
        "name": "Xã Tân Tiến",
        "codename": "xa_tan_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1201,
        "code": 12586,
        "name": "Xã Hưng Hà",
        "codename": "xa_hung_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1202,
        "code": 12595,
        "name": "Xã Ngự Thiên",
        "codename": "xa_ngu_thien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1203,
        "code": 12613,
        "name": "Xã Long Hưng",
        "codename": "xa_long_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1204,
        "code": 12619,
        "name": "Xã Diên Hà",
        "codename": "xa_dien_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1205,
        "code": 12631,
        "name": "Xã Thần Khê",
        "codename": "xa_than_khe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1206,
        "code": 12634,
        "name": "Xã Tiên La",
        "codename": "xa_tien_la",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1207,
        "code": 12676,
        "name": "Xã Lê Quý Đôn",
        "codename": "xa_le_quy_don",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1208,
        "code": 12685,
        "name": "Xã Hồng Minh",
        "codename": "xa_hong_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1209,
        "code": 12688,
        "name": "Xã Đông Hưng",
        "codename": "xa_dong_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1210,
        "code": 12694,
        "name": "Xã Bắc Đông Hưng",
        "codename": "xa_bac_dong_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1211,
        "code": 12700,
        "name": "Xã Bắc Tiên Hưng",
        "codename": "xa_bac_tien_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1212,
        "code": 12736,
        "name": "Xã Đông Tiên Hưng",
        "codename": "xa_dong_tien_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1213,
        "code": 12745,
        "name": "Xã Bắc Đông Quan",
        "codename": "xa_bac_dong_quan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1214,
        "code": 12754,
        "name": "Xã Tiên Hưng",
        "codename": "xa_tien_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1215,
        "code": 12763,
        "name": "Xã Nam Tiên Hưng",
        "codename": "xa_nam_tien_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1216,
        "code": 12775,
        "name": "Xã Nam Đông Hưng",
        "codename": "xa_nam_dong_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1217,
        "code": 12793,
        "name": "Xã Đông Quan",
        "codename": "xa_dong_quan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1218,
        "code": 12817,
        "name": "Phường Trà Lý",
        "codename": "phuong_tra_ly",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1219,
        "code": 12826,
        "name": "Xã Thái Thụy",
        "codename": "xa_thai_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1220,
        "code": 12850,
        "name": "Xã Tây Thụy Anh",
        "codename": "xa_tay_thuy_anh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1221,
        "code": 12859,
        "name": "Xã Bắc Thụy Anh",
        "codename": "xa_bac_thuy_anh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1222,
        "code": 12862,
        "name": "Xã Đông Thụy Anh",
        "codename": "xa_dong_thuy_anh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1223,
        "code": 12865,
        "name": "Xã Thụy Anh",
        "codename": "xa_thuy_anh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1224,
        "code": 12904,
        "name": "Xã Nam Thụy Anh",
        "codename": "xa_nam_thuy_anh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1225,
        "code": 12916,
        "name": "Xã Bắc Thái Ninh",
        "codename": "xa_bac_thai_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1226,
        "code": 12919,
        "name": "Xã Tây Thái Ninh",
        "codename": "xa_tay_thai_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1227,
        "code": 12922,
        "name": "Xã Thái Ninh",
        "codename": "xa_thai_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1228,
        "code": 12943,
        "name": "Xã Đông Thái Ninh",
        "codename": "xa_dong_thai_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1229,
        "code": 12961,
        "name": "Xã Nam Thái Ninh",
        "codename": "xa_nam_thai_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1230,
        "code": 12970,
        "name": "Xã Tiền Hải",
        "codename": "xa_tien_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1231,
        "code": 12988,
        "name": "Xã Đông Tiền Hải",
        "codename": "xa_dong_tien_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1232,
        "code": 13003,
        "name": "Xã Đồng Châu",
        "codename": "xa_dong_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1233,
        "code": 13021,
        "name": "Xã Ái Quốc",
        "codename": "xa_ai_quoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1234,
        "code": 13039,
        "name": "Xã Tây Tiền Hải",
        "codename": "xa_tay_tien_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1235,
        "code": 13057,
        "name": "Xã Nam Cường",
        "codename": "xa_nam_cuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1236,
        "code": 13063,
        "name": "Xã Nam Tiền Hải",
        "codename": "xa_nam_tien_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1237,
        "code": 13066,
        "name": "Xã Hưng Phú",
        "codename": "xa_hung_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1238,
        "code": 13075,
        "name": "Xã Kiến Xương",
        "codename": "xa_kien_xuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1239,
        "code": 13093,
        "name": "Xã Trà Giang",
        "codename": "xa_tra_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1240,
        "code": 13096,
        "name": "Xã Bình Nguyên",
        "codename": "xa_binh_nguyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1241,
        "code": 13120,
        "name": "Xã Lê Lợi",
        "codename": "xa_le_loi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1242,
        "code": 13132,
        "name": "Xã Quang Lịch",
        "codename": "xa_quang_lich",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1243,
        "code": 13141,
        "name": "Xã Vũ Quý",
        "codename": "xa_vu_quy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1244,
        "code": 13159,
        "name": "Xã Hồng Vũ",
        "codename": "xa_hong_vu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1245,
        "code": 13183,
        "name": "Xã Bình Thanh",
        "codename": "xa_binh_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1246,
        "code": 13186,
        "name": "Xã Bình Định",
        "codename": "xa_binh_dinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1247,
        "code": 13192,
        "name": "Xã Vũ Thư",
        "codename": "xa_vu_thu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1248,
        "code": 13219,
        "name": "Xã Vạn Xuân",
        "codename": "xa_van_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1249,
        "code": 13222,
        "name": "Xã Thư Trì",
        "codename": "xa_thu_tri",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1250,
        "code": 13225,
        "name": "Phường Thái Bình",
        "codename": "phuong_thai_binh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1251,
        "code": 13246,
        "name": "Xã Tân Thuận",
        "codename": "xa_tan_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1252,
        "code": 13264,
        "name": "Xã Thư Vũ",
        "codename": "xa_thu_vu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1253,
        "code": 13279,
        "name": "Xã Vũ Tiên",
        "codename": "xa_vu_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 33
    },
    {
        "id": 1254,
        "code": 37,
        "name": "Tỉnh Ninh Bình",
        "codename": "ninh_binh",
        "division_type": "tỉnh",
        "phone_code": 229,
        "parent_code": null
    },
    {
        "id": 1255,
        "code": 13285,
        "name": "Phường Phủ Lý",
        "codename": "phuong_phu_ly",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1256,
        "code": 13291,
        "name": "Phường Phù Vân",
        "codename": "phuong_phu_van",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1257,
        "code": 13318,
        "name": "Phường Châu Sơn",
        "codename": "phuong_chau_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1258,
        "code": 13324,
        "name": "Phường Duy Tiên",
        "codename": "phuong_duy_tien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1259,
        "code": 13330,
        "name": "Phường Duy Tân",
        "codename": "phuong_duy_tan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1260,
        "code": 13336,
        "name": "Phường Duy Hà",
        "codename": "phuong_duy_ha",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1261,
        "code": 13348,
        "name": "Phường Đồng Văn",
        "codename": "phuong_dong_van",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1262,
        "code": 13363,
        "name": "Phường Tiên Sơn",
        "codename": "phuong_tien_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1263,
        "code": 13366,
        "name": "Phường Hà Nam",
        "codename": "phuong_ha_nam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1264,
        "code": 13384,
        "name": "Phường Kim Bảng",
        "codename": "phuong_kim_bang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1265,
        "code": 13393,
        "name": "Phường Lê Hồ",
        "codename": "phuong_le_ho",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1266,
        "code": 13396,
        "name": "Phường Nguyễn Uý",
        "codename": "phuong_nguyen_uy",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1267,
        "code": 13402,
        "name": "Phường Kim Thanh",
        "codename": "phuong_kim_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1268,
        "code": 13420,
        "name": "Phường Tam Chúc",
        "codename": "phuong_tam_chuc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1269,
        "code": 13435,
        "name": "Phường Lý Thường Kiệt",
        "codename": "phuong_ly_thuong_kiet",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1270,
        "code": 13444,
        "name": "Phường Liêm Tuyền",
        "codename": "phuong_liem_tuyen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1271,
        "code": 13456,
        "name": "Xã Liêm Hà",
        "codename": "xa_liem_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1272,
        "code": 13474,
        "name": "Xã Tân Thanh",
        "codename": "xa_tan_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1273,
        "code": 13483,
        "name": "Xã Thanh Bình",
        "codename": "xa_thanh_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1274,
        "code": 13489,
        "name": "Xã Thanh Lâm",
        "codename": "xa_thanh_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1275,
        "code": 13495,
        "name": "Xã Thanh Liêm",
        "codename": "xa_thanh_liem",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1276,
        "code": 13501,
        "name": "Xã Bình Mỹ",
        "codename": "xa_binh_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1277,
        "code": 13504,
        "name": "Xã Bình Lục",
        "codename": "xa_binh_luc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1278,
        "code": 13531,
        "name": "Xã Bình Giang",
        "codename": "xa_binh_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1279,
        "code": 13540,
        "name": "Xã Bình An",
        "codename": "xa_binh_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1280,
        "code": 13558,
        "name": "Xã Bình Sơn",
        "codename": "xa_binh_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1281,
        "code": 13573,
        "name": "Xã Lý Nhân",
        "codename": "xa_ly_nhan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1282,
        "code": 13579,
        "name": "Xã Bắc Lý",
        "codename": "xa_bac_ly",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1283,
        "code": 13591,
        "name": "Xã Nam Xang",
        "codename": "xa_nam_xang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1284,
        "code": 13594,
        "name": "Xã Trần Thương",
        "codename": "xa_tran_thuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1285,
        "code": 13597,
        "name": "Xã Vĩnh Trụ",
        "codename": "xa_vinh_tru",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1286,
        "code": 13609,
        "name": "Xã Nhân Hà",
        "codename": "xa_nhan_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1287,
        "code": 13627,
        "name": "Xã Nam Lý",
        "codename": "xa_nam_ly",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1288,
        "code": 13669,
        "name": "Phường Nam Định",
        "codename": "phuong_nam_dinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1289,
        "code": 13684,
        "name": "Phường Thiên Trường",
        "codename": "phuong_thien_truong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1290,
        "code": 13693,
        "name": "Phường Đông A",
        "codename": "phuong_dong_a",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1291,
        "code": 13699,
        "name": "Phường Thành Nam",
        "codename": "phuong_thanh_nam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1292,
        "code": 13708,
        "name": "Phường Mỹ Lộc",
        "codename": "phuong_my_loc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1293,
        "code": 13741,
        "name": "Xã Vụ Bản",
        "codename": "xa_vu_ban",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1294,
        "code": 13750,
        "name": "Xã Minh Tân",
        "codename": "xa_minh_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1295,
        "code": 13753,
        "name": "Xã Hiển Khánh",
        "codename": "xa_hien_khanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1296,
        "code": 13777,
        "name": "Phường Trường Thi",
        "codename": "phuong_truong_thi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1297,
        "code": 13786,
        "name": "Xã Liên Minh",
        "codename": "xa_lien_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1298,
        "code": 13795,
        "name": "Xã Ý Yên",
        "codename": "xa_y_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1299,
        "code": 13807,
        "name": "Xã Tân Minh",
        "codename": "xa_tan_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1300,
        "code": 13822,
        "name": "Xã Phong Doanh",
        "codename": "xa_phong_doanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1301,
        "code": 13834,
        "name": "Xã Vũ Dương",
        "codename": "xa_vu_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1302,
        "code": 13864,
        "name": "Xã Vạn Thắng",
        "codename": "xa_van_thang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1303,
        "code": 13870,
        "name": "Xã Yên Cường",
        "codename": "xa_yen_cuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1304,
        "code": 13879,
        "name": "Xã Yên Đồng",
        "codename": "xa_yen_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1305,
        "code": 13891,
        "name": "Xã Nghĩa Hưng",
        "codename": "xa_nghia_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1306,
        "code": 13894,
        "name": "Xã Rạng Đông",
        "codename": "xa_rang_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1307,
        "code": 13900,
        "name": "Xã Đồng Thịnh",
        "codename": "xa_dong_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1308,
        "code": 13918,
        "name": "Xã Nghĩa Sơn",
        "codename": "xa_nghia_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1309,
        "code": 13927,
        "name": "Xã Hồng Phong",
        "codename": "xa_hong_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1310,
        "code": 13939,
        "name": "Xã Quỹ Nhất",
        "codename": "xa_quy_nhat",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1311,
        "code": 13957,
        "name": "Xã Nghĩa Lâm",
        "codename": "xa_nghia_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1312,
        "code": 13966,
        "name": "Xã Nam Trực",
        "codename": "xa_nam_truc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1313,
        "code": 13972,
        "name": "Phường Vị Khê",
        "codename": "phuong_vi_khe",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1314,
        "code": 13984,
        "name": "Phường Hồng Quang",
        "codename": "phuong_hong_quang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1315,
        "code": 13987,
        "name": "Xã Nam Hồng",
        "codename": "xa_nam_hong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1316,
        "code": 14005,
        "name": "Xã Nam Ninh",
        "codename": "xa_nam_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1317,
        "code": 14011,
        "name": "Xã Nam Minh",
        "codename": "xa_nam_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1318,
        "code": 14014,
        "name": "Xã Nam Đồng",
        "codename": "xa_nam_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1319,
        "code": 14026,
        "name": "Xã Cổ Lễ",
        "codename": "xa_co_le",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1320,
        "code": 14038,
        "name": "Xã Ninh Giang",
        "codename": "xa_ninh_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1321,
        "code": 14053,
        "name": "Xã Trực Ninh",
        "codename": "xa_truc_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1322,
        "code": 14056,
        "name": "Xã Cát Thành",
        "codename": "xa_cat_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1323,
        "code": 14062,
        "name": "Xã Quang Hưng",
        "codename": "xa_quang_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1324,
        "code": 14071,
        "name": "Xã Minh Thái",
        "codename": "xa_minh_thai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1325,
        "code": 14077,
        "name": "Xã Ninh Cường",
        "codename": "xa_ninh_cuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1326,
        "code": 14089,
        "name": "Xã Xuân Trường",
        "codename": "xa_xuan_truong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1327,
        "code": 14095,
        "name": "Xã Xuân Hồng",
        "codename": "xa_xuan_hong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1328,
        "code": 14104,
        "name": "Xã Xuân Giang",
        "codename": "xa_xuan_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1329,
        "code": 14122,
        "name": "Xã Xuân Hưng",
        "codename": "xa_xuan_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1330,
        "code": 14161,
        "name": "Xã Giao Minh",
        "codename": "xa_giao_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1331,
        "code": 14167,
        "name": "Xã Giao Thuỷ",
        "codename": "xa_giao_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1332,
        "code": 14179,
        "name": "Xã Giao Hưng",
        "codename": "xa_giao_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1333,
        "code": 14182,
        "name": "Xã Giao Hoà",
        "codename": "xa_giao_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1334,
        "code": 14194,
        "name": "Xã Giao Bình",
        "codename": "xa_giao_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1335,
        "code": 14203,
        "name": "Xã Giao Phúc",
        "codename": "xa_giao_phuc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1336,
        "code": 14212,
        "name": "Xã Giao Ninh",
        "codename": "xa_giao_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1337,
        "code": 14215,
        "name": "Xã Hải Hậu",
        "codename": "xa_hai_hau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1338,
        "code": 14218,
        "name": "Xã Hải Tiến",
        "codename": "xa_hai_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1339,
        "code": 14221,
        "name": "Xã Hải Thịnh",
        "codename": "xa_hai_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1340,
        "code": 14236,
        "name": "Xã Hải Anh",
        "codename": "xa_hai_anh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1341,
        "code": 14248,
        "name": "Xã Hải Hưng",
        "codename": "xa_hai_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1342,
        "code": 14281,
        "name": "Xã Hải An",
        "codename": "xa_hai_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1343,
        "code": 14287,
        "name": "Xã Hải Quang",
        "codename": "xa_hai_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1344,
        "code": 14308,
        "name": "Xã Hải Xuân",
        "codename": "xa_hai_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1345,
        "code": 14329,
        "name": "Phường Hoa Lư",
        "codename": "phuong_hoa_lu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1346,
        "code": 14359,
        "name": "Phường Nam Hoa Lư",
        "codename": "phuong_nam_hoa_lu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1347,
        "code": 14362,
        "name": "Phường Tam Điệp",
        "codename": "phuong_tam_diep",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1348,
        "code": 14365,
        "name": "Phường Trung Sơn",
        "codename": "phuong_trung_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1349,
        "code": 14371,
        "name": "Phường Yên Sơn",
        "codename": "phuong_yen_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1350,
        "code": 14389,
        "name": "Xã Gia Lâm",
        "codename": "xa_gia_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1351,
        "code": 14401,
        "name": "Xã Gia Tường",
        "codename": "xa_gia_tuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1352,
        "code": 14404,
        "name": "Xã Cúc Phương",
        "codename": "xa_cuc_phuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1353,
        "code": 14407,
        "name": "Xã Phú Sơn",
        "codename": "xa_phu_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1354,
        "code": 14428,
        "name": "Xã Nho Quan",
        "codename": "xa_nho_quan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1355,
        "code": 14434,
        "name": "Xã Thanh Sơn",
        "codename": "xa_thanh_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1356,
        "code": 14452,
        "name": "Xã Quỳnh Lưu",
        "codename": "xa_quynh_luu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1357,
        "code": 14458,
        "name": "Xã Phú Long",
        "codename": "xa_phu_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1358,
        "code": 14464,
        "name": "Xã Gia Viễn",
        "codename": "xa_gia_vien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1359,
        "code": 14482,
        "name": "Xã Gia Hưng",
        "codename": "xa_gia_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1360,
        "code": 14488,
        "name": "Xã Gia Vân",
        "codename": "xa_gia_van",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1361,
        "code": 14494,
        "name": "Xã Gia Trấn",
        "codename": "xa_gia_tran",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1362,
        "code": 14500,
        "name": "Xã Đại Hoàng",
        "codename": "xa_dai_hoang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1363,
        "code": 14524,
        "name": "Xã Gia Phong",
        "codename": "xa_gia_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1364,
        "code": 14533,
        "name": "Phường Tây Hoa Lư",
        "codename": "phuong_tay_hoa_lu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1365,
        "code": 14560,
        "name": "Xã Yên Khánh",
        "codename": "xa_yen_khanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1366,
        "code": 14563,
        "name": "Xã Khánh Thiện",
        "codename": "xa_khanh_thien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1367,
        "code": 14566,
        "name": "Phường Đông Hoa Lư",
        "codename": "phuong_dong_hoa_lu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1368,
        "code": 14608,
        "name": "Xã Khánh Trung",
        "codename": "xa_khanh_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1369,
        "code": 14611,
        "name": "Xã Khánh Nhạc",
        "codename": "xa_khanh_nhac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1370,
        "code": 14614,
        "name": "Xã Khánh Hội",
        "codename": "xa_khanh_hoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1371,
        "code": 14620,
        "name": "Xã Phát Diệm",
        "codename": "xa_phat_diem",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1372,
        "code": 14623,
        "name": "Xã Bình Minh",
        "codename": "xa_binh_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1373,
        "code": 14638,
        "name": "Xã Kim Sơn",
        "codename": "xa_kim_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1374,
        "code": 14647,
        "name": "Xã Quang Thiện",
        "codename": "xa_quang_thien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1375,
        "code": 14653,
        "name": "Xã Chất Bình",
        "codename": "xa_chat_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1376,
        "code": 14674,
        "name": "Xã Lai Thành",
        "codename": "xa_lai_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1377,
        "code": 14677,
        "name": "Xã Định Hóa",
        "codename": "xa_dinh_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1378,
        "code": 14698,
        "name": "Xã Kim Đông",
        "codename": "xa_kim_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1379,
        "code": 14701,
        "name": "Xã Yên Mô",
        "codename": "xa_yen_mo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1380,
        "code": 14725,
        "name": "Phường Yên Thắng",
        "codename": "phuong_yen_thang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1381,
        "code": 14728,
        "name": "Xã Yên Từ",
        "codename": "xa_yen_tu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1382,
        "code": 14743,
        "name": "Xã Yên Mạc",
        "codename": "xa_yen_mac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1383,
        "code": 14746,
        "name": "Xã Đồng Thái",
        "codename": "xa_dong_thai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 37
    },
    {
        "id": 1384,
        "code": 38,
        "name": "Tỉnh Thanh Hóa",
        "codename": "thanh_hoa",
        "division_type": "tỉnh",
        "phone_code": 237,
        "parent_code": null
    },
    {
        "id": 1385,
        "code": 14758,
        "name": "Phường Hàm Rồng",
        "codename": "phuong_ham_rong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1386,
        "code": 14797,
        "name": "Phường Hạc Thành",
        "codename": "phuong_hac_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1387,
        "code": 14812,
        "name": "Phường Bỉm Sơn",
        "codename": "phuong_bim_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1388,
        "code": 14818,
        "name": "Phường Quang Trung",
        "codename": "phuong_quang_trung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1389,
        "code": 14845,
        "name": "Xã Mường Lát",
        "codename": "xa_muong_lat",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1390,
        "code": 14848,
        "name": "Xã Tam Chung",
        "codename": "xa_tam_chung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1391,
        "code": 14854,
        "name": "Xã Mường Lý",
        "codename": "xa_muong_ly",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1392,
        "code": 14857,
        "name": "Xã Trung Lý",
        "codename": "xa_trung_ly",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1393,
        "code": 14860,
        "name": "Xã Quang Chiểu",
        "codename": "xa_quang_chieu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1394,
        "code": 14863,
        "name": "Xã Pù Nhi",
        "codename": "xa_pu_nhi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1395,
        "code": 14864,
        "name": "Xã Nhi Sơn",
        "codename": "xa_nhi_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1396,
        "code": 14866,
        "name": "Xã Mường Chanh",
        "codename": "xa_muong_chanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1397,
        "code": 14869,
        "name": "Xã Hồi Xuân",
        "codename": "xa_hoi_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1398,
        "code": 14872,
        "name": "Xã Trung Thành",
        "codename": "xa_trung_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1399,
        "code": 14875,
        "name": "Xã Trung Sơn",
        "codename": "xa_trung_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1400,
        "code": 14878,
        "name": "Xã Phú Lệ",
        "codename": "xa_phu_le",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1401,
        "code": 14890,
        "name": "Xã Phú Xuân",
        "codename": "xa_phu_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1402,
        "code": 14896,
        "name": "Xã Hiền Kiệt",
        "codename": "xa_hien_kiet",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1403,
        "code": 14902,
        "name": "Xã Nam Xuân",
        "codename": "xa_nam_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1404,
        "code": 14908,
        "name": "Xã Thiên Phủ",
        "codename": "xa_thien_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1405,
        "code": 14923,
        "name": "Xã Bá Thước",
        "codename": "xa_ba_thuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1406,
        "code": 14932,
        "name": "Xã Điền Quang",
        "codename": "xa_dien_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1407,
        "code": 14950,
        "name": "Xã Điền Lư",
        "codename": "xa_dien_lu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1408,
        "code": 14953,
        "name": "Xã Quý Lương",
        "codename": "xa_quy_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1409,
        "code": 14956,
        "name": "Xã Pù Luông",
        "codename": "xa_pu_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1410,
        "code": 14959,
        "name": "Xã Cổ Lũng",
        "codename": "xa_co_lung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1411,
        "code": 14974,
        "name": "Xã Văn Nho",
        "codename": "xa_van_nho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1412,
        "code": 14980,
        "name": "Xã Thiết Ống",
        "codename": "xa_thiet_ong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1413,
        "code": 15001,
        "name": "Xã Trung Hạ",
        "codename": "xa_trung_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1414,
        "code": 15007,
        "name": "Xã Tam Thanh",
        "codename": "xa_tam_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1415,
        "code": 15010,
        "name": "Xã Sơn Thủy",
        "codename": "xa_son_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1416,
        "code": 15013,
        "name": "Xã Na Mèo",
        "codename": "xa_na_meo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1417,
        "code": 15016,
        "name": "Xã Quan Sơn",
        "codename": "xa_quan_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1418,
        "code": 15019,
        "name": "Xã Tam Lư",
        "codename": "xa_tam_lu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1419,
        "code": 15022,
        "name": "Xã Sơn Điện",
        "codename": "xa_son_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1420,
        "code": 15025,
        "name": "Xã Mường Mìn",
        "codename": "xa_muong_min",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1421,
        "code": 15031,
        "name": "Xã Yên Khương",
        "codename": "xa_yen_khuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1422,
        "code": 15034,
        "name": "Xã Yên Thắng",
        "codename": "xa_yen_thang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1423,
        "code": 15043,
        "name": "Xã Giao An",
        "codename": "xa_giao_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1424,
        "code": 15049,
        "name": "Xã Văn Phú",
        "codename": "xa_van_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1425,
        "code": 15055,
        "name": "Xã Linh Sơn",
        "codename": "xa_linh_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1426,
        "code": 15058,
        "name": "Xã Đồng Lương",
        "codename": "xa_dong_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1427,
        "code": 15061,
        "name": "Xã Ngọc Lặc",
        "codename": "xa_ngoc_lac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1428,
        "code": 15085,
        "name": "Xã Thạch Lập",
        "codename": "xa_thach_lap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1429,
        "code": 15091,
        "name": "Xã Ngọc Liên",
        "codename": "xa_ngoc_lien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1430,
        "code": 15106,
        "name": "Xã Nguyệt Ấn",
        "codename": "xa_nguyet_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1431,
        "code": 15112,
        "name": "Xã Kiên Thọ",
        "codename": "xa_kien_tho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1432,
        "code": 15124,
        "name": "Xã Minh Sơn",
        "codename": "xa_minh_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1433,
        "code": 15127,
        "name": "Xã Cẩm Thủy",
        "codename": "xa_cam_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1434,
        "code": 15142,
        "name": "Xã Cẩm Thạch",
        "codename": "xa_cam_thach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1435,
        "code": 15148,
        "name": "Xã Cẩm Tú",
        "codename": "xa_cam_tu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1436,
        "code": 15163,
        "name": "Xã Cẩm Vân",
        "codename": "xa_cam_van",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1437,
        "code": 15178,
        "name": "Xã Cẩm Tân",
        "codename": "xa_cam_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1438,
        "code": 15187,
        "name": "Xã Kim Tân",
        "codename": "xa_kim_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1439,
        "code": 15190,
        "name": "Xã Vân Du",
        "codename": "xa_van_du",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1440,
        "code": 15199,
        "name": "Xã Thạch Quảng",
        "codename": "xa_thach_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1441,
        "code": 15211,
        "name": "Xã Thạch Bình",
        "codename": "xa_thach_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1442,
        "code": 15229,
        "name": "Xã Thành Vinh",
        "codename": "xa_thanh_vinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1443,
        "code": 15250,
        "name": "Xã Ngọc Trạo",
        "codename": "xa_ngoc_trao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1444,
        "code": 15271,
        "name": "Xã Hà Trung",
        "codename": "xa_ha_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1445,
        "code": 15274,
        "name": "Xã Hà Long",
        "codename": "xa_ha_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1446,
        "code": 15286,
        "name": "Xã Hoạt Giang",
        "codename": "xa_hoat_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1447,
        "code": 15298,
        "name": "Xã Lĩnh Toại",
        "codename": "xa_linh_toai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1448,
        "code": 15316,
        "name": "Xã Tống Sơn",
        "codename": "xa_tong_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1449,
        "code": 15349,
        "name": "Xã Vĩnh Lộc",
        "codename": "xa_vinh_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1450,
        "code": 15361,
        "name": "Xã Tây Đô",
        "codename": "xa_tay_do",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1451,
        "code": 15382,
        "name": "Xã Biện Thượng",
        "codename": "xa_bien_thuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1452,
        "code": 15409,
        "name": "Xã Yên Phú",
        "codename": "xa_yen_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1453,
        "code": 15412,
        "name": "Xã Quý Lộc",
        "codename": "xa_quy_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1454,
        "code": 15421,
        "name": "Xã Yên Trường",
        "codename": "xa_yen_truong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1455,
        "code": 15442,
        "name": "Xã Yên Ninh",
        "codename": "xa_yen_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1456,
        "code": 15448,
        "name": "Xã Định Hòa",
        "codename": "xa_dinh_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1457,
        "code": 15457,
        "name": "Xã Định Tân",
        "codename": "xa_dinh_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1458,
        "code": 15469,
        "name": "Xã Yên Định",
        "codename": "xa_yen_dinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1459,
        "code": 15499,
        "name": "Xã Thọ Xuân",
        "codename": "xa_tho_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1460,
        "code": 15505,
        "name": "Xã Thọ Long",
        "codename": "xa_tho_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1461,
        "code": 15520,
        "name": "Xã Xuân Hòa",
        "codename": "xa_xuan_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1462,
        "code": 15544,
        "name": "Xã Lam Sơn",
        "codename": "xa_lam_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1463,
        "code": 15553,
        "name": "Xã Sao Vàng",
        "codename": "xa_sao_vang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1464,
        "code": 15568,
        "name": "Xã Thọ Lập",
        "codename": "xa_tho_lap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1465,
        "code": 15574,
        "name": "Xã Xuân Tín",
        "codename": "xa_xuan_tin",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1466,
        "code": 15592,
        "name": "Xã Xuân Lập",
        "codename": "xa_xuan_lap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1467,
        "code": 15607,
        "name": "Xã Bát Mọt",
        "codename": "xa_bat_mot",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1468,
        "code": 15610,
        "name": "Xã Yên Nhân",
        "codename": "xa_yen_nhan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1469,
        "code": 15622,
        "name": "Xã Vạn Xuân",
        "codename": "xa_van_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1470,
        "code": 15628,
        "name": "Xã Lương Sơn",
        "codename": "xa_luong_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1471,
        "code": 15634,
        "name": "Xã Luận Thành",
        "codename": "xa_luan_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1472,
        "code": 15643,
        "name": "Xã Thắng Lộc",
        "codename": "xa_thang_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1473,
        "code": 15646,
        "name": "Xã Thường Xuân",
        "codename": "xa_thuong_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1474,
        "code": 15658,
        "name": "Xã Xuân Chinh",
        "codename": "xa_xuan_chinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1475,
        "code": 15661,
        "name": "Xã Tân Thành",
        "codename": "xa_tan_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1476,
        "code": 15664,
        "name": "Xã Triệu Sơn",
        "codename": "xa_trieu_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1477,
        "code": 15667,
        "name": "Xã Thọ Bình",
        "codename": "xa_tho_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1478,
        "code": 15682,
        "name": "Xã Hợp Tiến",
        "codename": "xa_hop_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1479,
        "code": 15715,
        "name": "Xã Tân Ninh",
        "codename": "xa_tan_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1480,
        "code": 15724,
        "name": "Xã Đồng Tiến",
        "codename": "xa_dong_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1481,
        "code": 15754,
        "name": "Xã Thọ Ngọc",
        "codename": "xa_tho_ngoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1482,
        "code": 15763,
        "name": "Xã Thọ Phú",
        "codename": "xa_tho_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1483,
        "code": 15766,
        "name": "Xã An Nông",
        "codename": "xa_an_nong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1484,
        "code": 15772,
        "name": "Xã Thiệu Hóa",
        "codename": "xa_thieu_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1485,
        "code": 15778,
        "name": "Xã Thiệu Tiến",
        "codename": "xa_thieu_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1486,
        "code": 15796,
        "name": "Xã Thiệu Quang",
        "codename": "xa_thieu_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1487,
        "code": 15820,
        "name": "Xã Thiệu Toán",
        "codename": "xa_thieu_toan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1488,
        "code": 15835,
        "name": "Xã Thiệu Trung",
        "codename": "xa_thieu_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1489,
        "code": 15853,
        "name": "Phường Đông Tiến",
        "codename": "phuong_dong_tien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1490,
        "code": 15865,
        "name": "Xã Hoằng Hóa",
        "codename": "xa_hoang_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1491,
        "code": 15880,
        "name": "Xã Hoằng Giang",
        "codename": "xa_hoang_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1492,
        "code": 15889,
        "name": "Xã Hoằng Phú",
        "codename": "xa_hoang_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1493,
        "code": 15910,
        "name": "Xã Hoằng Sơn",
        "codename": "xa_hoang_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1494,
        "code": 15925,
        "name": "Phường Nguyệt Viên",
        "codename": "phuong_nguyet_vien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1495,
        "code": 15961,
        "name": "Xã Hoằng Lộc",
        "codename": "xa_hoang_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1496,
        "code": 15976,
        "name": "Xã Hoằng Châu",
        "codename": "xa_hoang_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1497,
        "code": 15991,
        "name": "Xã Hoằng Tiến",
        "codename": "xa_hoang_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1498,
        "code": 16000,
        "name": "Xã Hoằng Thanh",
        "codename": "xa_hoang_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1499,
        "code": 16012,
        "name": "Xã Hậu Lộc",
        "codename": "xa_hau_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1500,
        "code": 16021,
        "name": "Xã Triệu Lộc",
        "codename": "xa_trieu_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1501,
        "code": 16033,
        "name": "Xã Đông Thành",
        "codename": "xa_dong_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1502,
        "code": 16072,
        "name": "Xã Hoa Lộc",
        "codename": "xa_hoa_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1503,
        "code": 16078,
        "name": "Xã Vạn Lộc",
        "codename": "xa_van_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1504,
        "code": 16093,
        "name": "Xã Nga Sơn",
        "codename": "xa_nga_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1505,
        "code": 16108,
        "name": "Xã Tân Tiến",
        "codename": "xa_tan_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1506,
        "code": 16114,
        "name": "Xã Nga Thắng",
        "codename": "xa_nga_thang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1507,
        "code": 16138,
        "name": "Xã Hồ Vương",
        "codename": "xa_ho_vuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1508,
        "code": 16144,
        "name": "Xã Nga An",
        "codename": "xa_nga_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1509,
        "code": 16171,
        "name": "Xã Ba Đình",
        "codename": "xa_ba_dinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1510,
        "code": 16174,
        "name": "Xã Như Xuân",
        "codename": "xa_nhu_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1511,
        "code": 16177,
        "name": "Xã Xuân Bình",
        "codename": "xa_xuan_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1512,
        "code": 16186,
        "name": "Xã Hóa Quỳ",
        "codename": "xa_hoa_quy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1513,
        "code": 16213,
        "name": "Xã Thanh Phong",
        "codename": "xa_thanh_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1514,
        "code": 16222,
        "name": "Xã Thanh Quân",
        "codename": "xa_thanh_quan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1515,
        "code": 16225,
        "name": "Xã Thượng Ninh",
        "codename": "xa_thuong_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1516,
        "code": 16228,
        "name": "Xã Như Thanh",
        "codename": "xa_nhu_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1517,
        "code": 16234,
        "name": "Xã Xuân Du",
        "codename": "xa_xuan_du",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1518,
        "code": 16249,
        "name": "Xã Mậu Lâm",
        "codename": "xa_mau_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1519,
        "code": 16258,
        "name": "Xã Xuân Thái",
        "codename": "xa_xuan_thai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1520,
        "code": 16264,
        "name": "Xã Yên Thọ",
        "codename": "xa_yen_tho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1521,
        "code": 16273,
        "name": "Xã Thanh Kỳ",
        "codename": "xa_thanh_ky",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1522,
        "code": 16279,
        "name": "Xã Nông Cống",
        "codename": "xa_nong_cong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1523,
        "code": 16297,
        "name": "Xã Trung Chính",
        "codename": "xa_trung_chinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1524,
        "code": 16309,
        "name": "Xã Thắng Lợi",
        "codename": "xa_thang_loi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1525,
        "code": 16342,
        "name": "Xã Thăng Bình",
        "codename": "xa_thang_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1526,
        "code": 16348,
        "name": "Xã Trường Văn",
        "codename": "xa_truong_van",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1527,
        "code": 16363,
        "name": "Xã Tượng Lĩnh",
        "codename": "xa_tuong_linh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1528,
        "code": 16369,
        "name": "Xã Công Chính",
        "codename": "xa_cong_chinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1529,
        "code": 16378,
        "name": "Phường Đông Sơn",
        "codename": "phuong_dong_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1530,
        "code": 16417,
        "name": "Phường Đông Quang",
        "codename": "phuong_dong_quang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1531,
        "code": 16438,
        "name": "Xã Lưu Vệ",
        "codename": "xa_luu_ve",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1532,
        "code": 16480,
        "name": "Xã Quảng Yên",
        "codename": "xa_quang_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1533,
        "code": 16489,
        "name": "Xã Quảng Chính",
        "codename": "xa_quang_chinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1534,
        "code": 16498,
        "name": "Xã Quảng Ngọc",
        "codename": "xa_quang_ngoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1535,
        "code": 16516,
        "name": "Phường Nam Sầm Sơn",
        "codename": "phuong_nam_sam_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1536,
        "code": 16522,
        "name": "Phường Quảng Phú",
        "codename": "phuong_quang_phu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1537,
        "code": 16531,
        "name": "Phường Sầm Sơn",
        "codename": "phuong_sam_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1538,
        "code": 16540,
        "name": "Xã Quảng Ninh",
        "codename": "xa_quang_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1539,
        "code": 16543,
        "name": "Xã Quảng Bình",
        "codename": "xa_quang_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1540,
        "code": 16549,
        "name": "Xã Tiên Trang",
        "codename": "xa_tien_trang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1541,
        "code": 16561,
        "name": "Phường Tĩnh Gia",
        "codename": "phuong_tinh_gia",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1542,
        "code": 16576,
        "name": "Phường Ngọc Sơn",
        "codename": "phuong_ngoc_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1543,
        "code": 16591,
        "name": "Xã Các Sơn",
        "codename": "xa_cac_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1544,
        "code": 16594,
        "name": "Phường Tân Dân",
        "codename": "phuong_tan_dan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1545,
        "code": 16597,
        "name": "Phường Hải Lĩnh",
        "codename": "phuong_hai_linh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1546,
        "code": 16609,
        "name": "Phường Đào Duy Từ",
        "codename": "phuong_dao_duy_tu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1547,
        "code": 16624,
        "name": "Phường Trúc Lâm",
        "codename": "phuong_truc_lam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1548,
        "code": 16636,
        "name": "Xã Trường Lâm",
        "codename": "xa_truong_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1549,
        "code": 16645,
        "name": "Phường Hải Bình",
        "codename": "phuong_hai_binh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1550,
        "code": 16654,
        "name": "Phường Nghi Sơn",
        "codename": "phuong_nghi_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 38
    },
    {
        "id": 1551,
        "code": 40,
        "name": "Tỉnh Nghệ An",
        "codename": "nghe_an",
        "division_type": "tỉnh",
        "phone_code": 238,
        "parent_code": null
    },
    {
        "id": 1552,
        "code": 16681,
        "name": "Phường Thành Vinh",
        "codename": "phuong_thanh_vinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1553,
        "code": 16690,
        "name": "Phường Trường Vinh",
        "codename": "phuong_truong_vinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1554,
        "code": 16702,
        "name": "Phường Vinh Phú",
        "codename": "phuong_vinh_phu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1555,
        "code": 16708,
        "name": "Phường Vinh Lộc",
        "codename": "phuong_vinh_loc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1556,
        "code": 16732,
        "name": "Phường Cửa Lò",
        "codename": "phuong_cua_lo",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1557,
        "code": 16738,
        "name": "Xã Quế Phong",
        "codename": "xa_que_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1558,
        "code": 16744,
        "name": "Xã Thông Thụ",
        "codename": "xa_thong_thu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1559,
        "code": 16750,
        "name": "Xã Tiền Phong",
        "codename": "xa_tien_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1560,
        "code": 16756,
        "name": "Xã Tri Lễ",
        "codename": "xa_tri_le",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1561,
        "code": 16774,
        "name": "Xã Mường Quàng",
        "codename": "xa_muong_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1562,
        "code": 16777,
        "name": "Xã Quỳ Châu",
        "codename": "xa_quy_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1563,
        "code": 16792,
        "name": "Xã Châu Tiến",
        "codename": "xa_chau_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1564,
        "code": 16801,
        "name": "Xã Hùng Chân",
        "codename": "xa_hung_chan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1565,
        "code": 16804,
        "name": "Xã Châu Bình",
        "codename": "xa_chau_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1566,
        "code": 16813,
        "name": "Xã Mường Xén",
        "codename": "xa_muong_xen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1567,
        "code": 16816,
        "name": "Xã Mỹ Lý",
        "codename": "xa_my_ly",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1568,
        "code": 16819,
        "name": "Xã Bắc Lý",
        "codename": "xa_bac_ly",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1569,
        "code": 16822,
        "name": "Xã Keng Đu",
        "codename": "xa_keng_du",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1570,
        "code": 16828,
        "name": "Xã Huồi Tụ",
        "codename": "xa_huoi_tu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1571,
        "code": 16831,
        "name": "Xã Mường Lống",
        "codename": "xa_muong_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1572,
        "code": 16834,
        "name": "Xã Na Loi",
        "codename": "xa_na_loi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1573,
        "code": 16837,
        "name": "Xã Nậm Cắn",
        "codename": "xa_nam_can",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1574,
        "code": 16849,
        "name": "Xã Hữu Kiệm",
        "codename": "xa_huu_kiem",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1575,
        "code": 16855,
        "name": "Xã Chiêu Lưu",
        "codename": "xa_chieu_luu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1576,
        "code": 16858,
        "name": "Xã Mường Típ",
        "codename": "xa_muong_tip",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1577,
        "code": 16870,
        "name": "Xã Na Ngoi",
        "codename": "xa_na_ngoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1578,
        "code": 16876,
        "name": "Xã Tương Dương",
        "codename": "xa_tuong_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1579,
        "code": 16882,
        "name": "Xã Nhôn Mai",
        "codename": "xa_nhon_mai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1580,
        "code": 16885,
        "name": "Xã Hữu Khuông",
        "codename": "xa_huu_khuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1581,
        "code": 16903,
        "name": "Xã Nga My",
        "codename": "xa_nga_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1582,
        "code": 16906,
        "name": "Xã Lượng Minh",
        "codename": "xa_luong_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1583,
        "code": 16909,
        "name": "Xã Yên Hòa",
        "codename": "xa_yen_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1584,
        "code": 16912,
        "name": "Xã Yên Na",
        "codename": "xa_yen_na",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1585,
        "code": 16933,
        "name": "Xã Tam Quang",
        "codename": "xa_tam_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1586,
        "code": 16936,
        "name": "Xã Tam Thái",
        "codename": "xa_tam_thai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1587,
        "code": 16939,
        "name": "Phường Thái Hòa",
        "codename": "phuong_thai_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1588,
        "code": 16941,
        "name": "Xã Nghĩa Đàn",
        "codename": "xa_nghia_dan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1589,
        "code": 16951,
        "name": "Xã Nghĩa Lâm",
        "codename": "xa_nghia_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1590,
        "code": 16969,
        "name": "Xã Nghĩa Thọ",
        "codename": "xa_nghia_tho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1591,
        "code": 16972,
        "name": "Xã Nghĩa Hưng",
        "codename": "xa_nghia_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1592,
        "code": 16975,
        "name": "Xã Nghĩa Mai",
        "codename": "xa_nghia_mai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1593,
        "code": 17011,
        "name": "Phường Tây Hiếu",
        "codename": "phuong_tay_hieu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1594,
        "code": 17017,
        "name": "Xã Đông Hiếu",
        "codename": "xa_dong_hieu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1595,
        "code": 17029,
        "name": "Xã Nghĩa Lộc",
        "codename": "xa_nghia_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1596,
        "code": 17032,
        "name": "Xã Nghĩa Khánh",
        "codename": "xa_nghia_khanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1597,
        "code": 17035,
        "name": "Xã Quỳ Hợp",
        "codename": "xa_quy_hop",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1598,
        "code": 17044,
        "name": "Xã Châu Hồng",
        "codename": "xa_chau_hong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1599,
        "code": 17056,
        "name": "Xã Châu Lộc",
        "codename": "xa_chau_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1600,
        "code": 17059,
        "name": "Xã Tam Hợp",
        "codename": "xa_tam_hop",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1601,
        "code": 17071,
        "name": "Xã Minh Hợp",
        "codename": "xa_minh_hop",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1602,
        "code": 17077,
        "name": "Xã Mường Ham",
        "codename": "xa_muong_ham",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1603,
        "code": 17089,
        "name": "Xã Mường Chọng",
        "codename": "xa_muong_chong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1604,
        "code": 17110,
        "name": "Phường Hoàng Mai",
        "codename": "phuong_hoang_mai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1605,
        "code": 17125,
        "name": "Phường Quỳnh Mai",
        "codename": "phuong_quynh_mai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1606,
        "code": 17128,
        "name": "Phường Tân Mai",
        "codename": "phuong_tan_mai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1607,
        "code": 17143,
        "name": "Xã Quỳnh Văn",
        "codename": "xa_quynh_van",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1608,
        "code": 17149,
        "name": "Xã Quỳnh Tam",
        "codename": "xa_quynh_tam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1609,
        "code": 17170,
        "name": "Xã Quỳnh Sơn",
        "codename": "xa_quynh_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1610,
        "code": 17176,
        "name": "Xã Quỳnh Anh",
        "codename": "xa_quynh_anh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1611,
        "code": 17179,
        "name": "Xã Quỳnh Lưu",
        "codename": "xa_quynh_luu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1612,
        "code": 17212,
        "name": "Xã Quỳnh Phú",
        "codename": "xa_quynh_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1613,
        "code": 17224,
        "name": "Xã Quỳnh Thắng",
        "codename": "xa_quynh_thang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1614,
        "code": 17230,
        "name": "Xã Bình Chuẩn",
        "codename": "xa_binh_chuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1615,
        "code": 17239,
        "name": "Xã Mậu Thạch",
        "codename": "xa_mau_thach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1616,
        "code": 17242,
        "name": "Xã Cam Phục",
        "codename": "xa_cam_phuc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1617,
        "code": 17248,
        "name": "Xã Châu Khê",
        "codename": "xa_chau_khe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1618,
        "code": 17254,
        "name": "Xã Con Cuông",
        "codename": "xa_con_cuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1619,
        "code": 17263,
        "name": "Xã Môn Sơn",
        "codename": "xa_mon_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1620,
        "code": 17266,
        "name": "Xã Tân Kỳ",
        "codename": "xa_tan_ky",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1621,
        "code": 17272,
        "name": "Xã Tân Phú",
        "codename": "xa_tan_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1622,
        "code": 17278,
        "name": "Xã Giai Xuân",
        "codename": "xa_giai_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1623,
        "code": 17284,
        "name": "Xã Nghĩa Đồng",
        "codename": "xa_nghia_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1624,
        "code": 17287,
        "name": "Xã Tiên Đồng",
        "codename": "xa_tien_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1625,
        "code": 17305,
        "name": "Xã Tân An",
        "codename": "xa_tan_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1626,
        "code": 17326,
        "name": "Xã Nghĩa Hành",
        "codename": "xa_nghia_hanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1627,
        "code": 17329,
        "name": "Xã Anh Sơn",
        "codename": "xa_anh_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1628,
        "code": 17335,
        "name": "Xã Thành Bình Thọ",
        "codename": "xa_thanh_binh_tho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1629,
        "code": 17344,
        "name": "Xã Nhân Hòa",
        "codename": "xa_nhan_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1630,
        "code": 17357,
        "name": "Xã Vĩnh Tường",
        "codename": "xa_vinh_tuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1631,
        "code": 17365,
        "name": "Xã Anh Sơn Đông",
        "codename": "xa_anh_son_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1632,
        "code": 17380,
        "name": "Xã Yên Xuân",
        "codename": "xa_yen_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1633,
        "code": 17395,
        "name": "Xã Hùng Châu",
        "codename": "xa_hung_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1634,
        "code": 17416,
        "name": "Xã Đức Châu",
        "codename": "xa_duc_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1635,
        "code": 17419,
        "name": "Xã Hải Châu",
        "codename": "xa_hai_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1636,
        "code": 17443,
        "name": "Xã Quảng Châu",
        "codename": "xa_quang_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1637,
        "code": 17464,
        "name": "Xã Diễn Châu",
        "codename": "xa_dien_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1638,
        "code": 17476,
        "name": "Xã Minh Châu",
        "codename": "xa_minh_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1639,
        "code": 17479,
        "name": "Xã An Châu",
        "codename": "xa_an_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1640,
        "code": 17488,
        "name": "Xã Tân Châu",
        "codename": "xa_tan_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1641,
        "code": 17506,
        "name": "Xã Yên Thành",
        "codename": "xa_yen_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1642,
        "code": 17515,
        "name": "Xã Bình Minh",
        "codename": "xa_binh_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1643,
        "code": 17521,
        "name": "Xã Quang Đồng",
        "codename": "xa_quang_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1644,
        "code": 17524,
        "name": "Xã Giai Lạc",
        "codename": "xa_giai_lac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1645,
        "code": 17530,
        "name": "Xã Đông Thành",
        "codename": "xa_dong_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1646,
        "code": 17560,
        "name": "Xã Vân Du",
        "codename": "xa_van_du",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1647,
        "code": 17569,
        "name": "Xã Quan Thành",
        "codename": "xa_quan_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1648,
        "code": 17605,
        "name": "Xã Hợp Minh",
        "codename": "xa_hop_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1649,
        "code": 17611,
        "name": "Xã Vân Tụ",
        "codename": "xa_van_tu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1650,
        "code": 17623,
        "name": "Xã Bạch Ngọc",
        "codename": "xa_bach_ngoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1651,
        "code": 17641,
        "name": "Xã Lương Sơn",
        "codename": "xa_luong_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1652,
        "code": 17662,
        "name": "Xã Đô Lương",
        "codename": "xa_do_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1653,
        "code": 17677,
        "name": "Xã Văn Hiến",
        "codename": "xa_van_hien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1654,
        "code": 17689,
        "name": "Xã Thuần Trung",
        "codename": "xa_thuan_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1655,
        "code": 17707,
        "name": "Xã Bạch Hà",
        "codename": "xa_bach_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1656,
        "code": 17713,
        "name": "Xã Đại Đồng",
        "codename": "xa_dai_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1657,
        "code": 17722,
        "name": "Xã Hạnh Lâm",
        "codename": "xa_hanh_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1658,
        "code": 17728,
        "name": "Xã Cát Ngạn",
        "codename": "xa_cat_ngan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1659,
        "code": 17743,
        "name": "Xã Tam Đồng",
        "codename": "xa_tam_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1660,
        "code": 17759,
        "name": "Xã Sơn Lâm",
        "codename": "xa_son_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1661,
        "code": 17770,
        "name": "Xã Hoa Quân",
        "codename": "xa_hoa_quan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1662,
        "code": 17779,
        "name": "Xã Xuân Lâm",
        "codename": "xa_xuan_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1663,
        "code": 17791,
        "name": "Xã Kim Bảng",
        "codename": "xa_kim_bang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1664,
        "code": 17818,
        "name": "Xã Bích Hào",
        "codename": "xa_bich_hao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1665,
        "code": 17827,
        "name": "Xã Nghi Lộc",
        "codename": "xa_nghi_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1666,
        "code": 17833,
        "name": "Xã Hải Lộc",
        "codename": "xa_hai_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1667,
        "code": 17842,
        "name": "Xã Thần Lĩnh",
        "codename": "xa_than_linh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1668,
        "code": 17854,
        "name": "Xã Văn Kiều",
        "codename": "xa_van_kieu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1669,
        "code": 17857,
        "name": "Xã Phúc Lộc",
        "codename": "xa_phuc_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1670,
        "code": 17866,
        "name": "Xã Trung Lộc",
        "codename": "xa_trung_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1671,
        "code": 17878,
        "name": "Xã Đông Lộc",
        "codename": "xa_dong_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1672,
        "code": 17920,
        "name": "Phường Vinh Hưng",
        "codename": "phuong_vinh_hung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1673,
        "code": 17935,
        "name": "Xã Nam Đàn",
        "codename": "xa_nam_dan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1674,
        "code": 17944,
        "name": "Xã Đại Huệ",
        "codename": "xa_dai_hue",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1675,
        "code": 17950,
        "name": "Xã Vạn An",
        "codename": "xa_van_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1676,
        "code": 17971,
        "name": "Xã Kim Liên",
        "codename": "xa_kim_lien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1677,
        "code": 17989,
        "name": "Xã Thiên Nhẫn",
        "codename": "xa_thien_nhan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1678,
        "code": 18001,
        "name": "Xã Hưng Nguyên",
        "codename": "xa_hung_nguyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1679,
        "code": 18007,
        "name": "Xã Yên Trung",
        "codename": "xa_yen_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1680,
        "code": 18028,
        "name": "Xã Hưng Nguyên Nam",
        "codename": "xa_hung_nguyen_nam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1681,
        "code": 18040,
        "name": "Xã Lam Thành",
        "codename": "xa_lam_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 40
    },
    {
        "id": 1682,
        "code": 42,
        "name": "Tỉnh Hà Tĩnh",
        "codename": "ha_tinh",
        "division_type": "tỉnh",
        "phone_code": 239,
        "parent_code": null
    },
    {
        "id": 1683,
        "code": 18073,
        "name": "Phường Thành Sen",
        "codename": "phuong_thanh_sen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1684,
        "code": 18100,
        "name": "Phường Trần Phú",
        "codename": "phuong_tran_phu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1685,
        "code": 18115,
        "name": "Phường Bắc Hồng Lĩnh",
        "codename": "phuong_bac_hong_linh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1686,
        "code": 18118,
        "name": "Phường Nam Hồng Lĩnh",
        "codename": "phuong_nam_hong_linh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1687,
        "code": 18133,
        "name": "Xã Hương Sơn",
        "codename": "xa_huong_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1688,
        "code": 18160,
        "name": "Xã Sơn Hồng",
        "codename": "xa_son_hong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1689,
        "code": 18163,
        "name": "Xã Sơn Tiến",
        "codename": "xa_son_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1690,
        "code": 18172,
        "name": "Xã Sơn Tây",
        "codename": "xa_son_tay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1691,
        "code": 18184,
        "name": "Xã Sơn Giang",
        "codename": "xa_son_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1692,
        "code": 18196,
        "name": "Xã Sơn Kim 1",
        "codename": "xa_son_kim_1",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1693,
        "code": 18199,
        "name": "Xã Sơn Kim 2",
        "codename": "xa_son_kim_2",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1694,
        "code": 18202,
        "name": "Xã Tứ Mỹ",
        "codename": "xa_tu_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1695,
        "code": 18223,
        "name": "Xã Kim Hoa",
        "codename": "xa_kim_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1696,
        "code": 18229,
        "name": "Xã Đức Thọ",
        "codename": "xa_duc_tho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1697,
        "code": 18244,
        "name": "Xã Đức Minh",
        "codename": "xa_duc_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1698,
        "code": 18262,
        "name": "Xã Đức Quang",
        "codename": "xa_duc_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1699,
        "code": 18277,
        "name": "Xã Đức Thịnh",
        "codename": "xa_duc_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1700,
        "code": 18304,
        "name": "Xã Đức Đồng",
        "codename": "xa_duc_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1701,
        "code": 18313,
        "name": "Xã Vũ Quang",
        "codename": "xa_vu_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1702,
        "code": 18322,
        "name": "Xã Mai Hoa",
        "codename": "xa_mai_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1703,
        "code": 18328,
        "name": "Xã Thượng Đức",
        "codename": "xa_thuong_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1704,
        "code": 18352,
        "name": "Xã Nghi Xuân",
        "codename": "xa_nghi_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1705,
        "code": 18364,
        "name": "Xã Đan Hải",
        "codename": "xa_dan_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1706,
        "code": 18373,
        "name": "Xã Tiên Điền",
        "codename": "xa_tien_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1707,
        "code": 18394,
        "name": "Xã Cổ Đạm",
        "codename": "xa_co_dam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1708,
        "code": 18406,
        "name": "Xã Can Lộc",
        "codename": "xa_can_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1709,
        "code": 18409,
        "name": "Xã Hồng Lộc",
        "codename": "xa_hong_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1710,
        "code": 18418,
        "name": "Xã Tùng Lộc",
        "codename": "xa_tung_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1711,
        "code": 18436,
        "name": "Xã Trường Lưu",
        "codename": "xa_truong_luu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1712,
        "code": 18466,
        "name": "Xã Gia Hanh",
        "codename": "xa_gia_hanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1713,
        "code": 18481,
        "name": "Xã Xuân Lộc",
        "codename": "xa_xuan_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1714,
        "code": 18484,
        "name": "Xã Đồng Lộc",
        "codename": "xa_dong_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1715,
        "code": 18496,
        "name": "Xã Hương Khê",
        "codename": "xa_huong_khe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1716,
        "code": 18502,
        "name": "Xã Hà Linh",
        "codename": "xa_ha_linh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1717,
        "code": 18523,
        "name": "Xã Hương Bình",
        "codename": "xa_huong_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1718,
        "code": 18532,
        "name": "Xã Hương Phố",
        "codename": "xa_huong_pho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1719,
        "code": 18544,
        "name": "Xã Hương Xuân",
        "codename": "xa_huong_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1720,
        "code": 18547,
        "name": "Xã Phúc Trạch",
        "codename": "xa_phuc_trach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1721,
        "code": 18550,
        "name": "Xã Hương Đô",
        "codename": "xa_huong_do",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1722,
        "code": 18562,
        "name": "Xã Thạch Hà",
        "codename": "xa_thach_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1723,
        "code": 18568,
        "name": "Xã Lộc Hà",
        "codename": "xa_loc_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1724,
        "code": 18583,
        "name": "Xã Mai Phụ",
        "codename": "xa_mai_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1725,
        "code": 18586,
        "name": "Xã Đông Kinh",
        "codename": "xa_dong_kinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1726,
        "code": 18601,
        "name": "Xã Việt Xuyên",
        "codename": "xa_viet_xuyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1727,
        "code": 18604,
        "name": "Xã Thạch Khê",
        "codename": "xa_thach_khe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1728,
        "code": 18619,
        "name": "Xã Đồng Tiến",
        "codename": "xa_dong_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1729,
        "code": 18628,
        "name": "Xã Thạch Lạc",
        "codename": "xa_thach_lac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1730,
        "code": 18634,
        "name": "Xã Toàn Lưu",
        "codename": "xa_toan_luu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1731,
        "code": 18652,
        "name": "Phường Hà Huy Tập",
        "codename": "phuong_ha_huy_tap",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1732,
        "code": 18667,
        "name": "Xã Thạch Xuân",
        "codename": "xa_thach_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1733,
        "code": 18673,
        "name": "Xã Cẩm Xuyên",
        "codename": "xa_cam_xuyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1734,
        "code": 18676,
        "name": "Xã Thiên Cầm",
        "codename": "xa_thien_cam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1735,
        "code": 18682,
        "name": "Xã Yên Hòa",
        "codename": "xa_yen_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1736,
        "code": 18685,
        "name": "Xã Cẩm Bình",
        "codename": "xa_cam_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1737,
        "code": 18736,
        "name": "Xã Cẩm Hưng",
        "codename": "xa_cam_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1738,
        "code": 18739,
        "name": "Xã Cẩm Duệ",
        "codename": "xa_cam_due",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1739,
        "code": 18742,
        "name": "Xã Cẩm Trung",
        "codename": "xa_cam_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1740,
        "code": 18748,
        "name": "Xã Cẩm Lạc",
        "codename": "xa_cam_lac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1741,
        "code": 18754,
        "name": "Phường Sông Trí",
        "codename": "phuong_song_tri",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1742,
        "code": 18766,
        "name": "Xã Kỳ Xuân",
        "codename": "xa_ky_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1743,
        "code": 18775,
        "name": "Xã Kỳ Anh",
        "codename": "xa_ky_anh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1744,
        "code": 18781,
        "name": "Phường Hải Ninh",
        "codename": "phuong_hai_ninh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1745,
        "code": 18787,
        "name": "Xã Kỳ Văn",
        "codename": "xa_ky_van",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1746,
        "code": 18790,
        "name": "Xã Kỳ Khang",
        "codename": "xa_ky_khang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1747,
        "code": 18814,
        "name": "Xã Kỳ Hoa",
        "codename": "xa_ky_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1748,
        "code": 18823,
        "name": "Phường Vũng Áng",
        "codename": "phuong_vung_ang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1749,
        "code": 18832,
        "name": "Phường Hoành Sơn",
        "codename": "phuong_hoanh_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1750,
        "code": 18838,
        "name": "Xã Kỳ Lạc",
        "codename": "xa_ky_lac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1751,
        "code": 18844,
        "name": "Xã Kỳ Thượng",
        "codename": "xa_ky_thuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 42
    },
    {
        "id": 1752,
        "code": 44,
        "name": "Tỉnh Quảng Trị",
        "codename": "quang_tri",
        "division_type": "tỉnh",
        "phone_code": 233,
        "parent_code": null
    },
    {
        "id": 1753,
        "code": 18859,
        "name": "Phường Đồng Thuận",
        "codename": "phuong_dong_thuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1754,
        "code": 18871,
        "name": "Phường Đồng Sơn",
        "codename": "phuong_dong_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1755,
        "code": 18880,
        "name": "Phường Đồng Hới",
        "codename": "phuong_dong_hoi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1756,
        "code": 18901,
        "name": "Xã Minh Hóa",
        "codename": "xa_minh_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1757,
        "code": 18904,
        "name": "Xã Dân Hóa",
        "codename": "xa_dan_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1758,
        "code": 18919,
        "name": "Xã Tân Thành",
        "codename": "xa_tan_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1759,
        "code": 18922,
        "name": "Xã Kim Điền",
        "codename": "xa_kim_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1760,
        "code": 18943,
        "name": "Xã Kim Phú",
        "codename": "xa_kim_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1761,
        "code": 18949,
        "name": "Xã Đồng Lê",
        "codename": "xa_dong_le",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1762,
        "code": 18952,
        "name": "Xã Tuyên Sơn",
        "codename": "xa_tuyen_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1763,
        "code": 18958,
        "name": "Xã Tuyên Lâm",
        "codename": "xa_tuyen_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1764,
        "code": 18985,
        "name": "Xã Tuyên Phú",
        "codename": "xa_tuyen_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1765,
        "code": 18991,
        "name": "Xã Tuyên Bình",
        "codename": "xa_tuyen_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1766,
        "code": 18997,
        "name": "Xã Tuyên Hóa",
        "codename": "xa_tuyen_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1767,
        "code": 19009,
        "name": "Phường Ba Đồn",
        "codename": "phuong_ba_don",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1768,
        "code": 19021,
        "name": "Xã Phú Trạch",
        "codename": "xa_phu_trach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1769,
        "code": 19030,
        "name": "Xã Trung Thuần",
        "codename": "xa_trung_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1770,
        "code": 19033,
        "name": "Xã Hòa Trạch",
        "codename": "xa_hoa_trach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1771,
        "code": 19051,
        "name": "Xã Tân Gianh",
        "codename": "xa_tan_gianh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1772,
        "code": 19057,
        "name": "Xã Quảng Trạch",
        "codename": "xa_quang_trach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1773,
        "code": 19066,
        "name": "Phường Bắc Gianh",
        "codename": "phuong_bac_gianh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1774,
        "code": 19075,
        "name": "Xã Nam Ba Đồn",
        "codename": "xa_nam_ba_don",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1775,
        "code": 19093,
        "name": "Xã Nam Gianh",
        "codename": "xa_nam_gianh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1776,
        "code": 19111,
        "name": "Xã Hoàn Lão",
        "codename": "xa_hoan_lao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1777,
        "code": 19126,
        "name": "Xã Bắc Trạch",
        "codename": "xa_bac_trach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1778,
        "code": 19138,
        "name": "Xã Phong Nha",
        "codename": "xa_phong_nha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1779,
        "code": 19141,
        "name": "Xã Bố Trạch",
        "codename": "xa_bo_trach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1780,
        "code": 19147,
        "name": "Xã Thượng Trạch",
        "codename": "xa_thuong_trach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1781,
        "code": 19159,
        "name": "Xã Đông Trạch",
        "codename": "xa_dong_trach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1782,
        "code": 19198,
        "name": "Xã Nam Trạch",
        "codename": "xa_nam_trach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1783,
        "code": 19204,
        "name": "Xã Trường Sơn",
        "codename": "xa_truong_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1784,
        "code": 19207,
        "name": "Xã Quảng Ninh",
        "codename": "xa_quang_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1785,
        "code": 19225,
        "name": "Xã Ninh Châu",
        "codename": "xa_ninh_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1786,
        "code": 19237,
        "name": "Xã Trường Ninh",
        "codename": "xa_truong_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1787,
        "code": 19246,
        "name": "Xã Lệ Ninh",
        "codename": "xa_le_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1788,
        "code": 19249,
        "name": "Xã Lệ Thủy",
        "codename": "xa_le_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1789,
        "code": 19255,
        "name": "Xã Cam Hồng",
        "codename": "xa_cam_hong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1790,
        "code": 19288,
        "name": "Xã Sen Ngư",
        "codename": "xa_sen_ngu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1791,
        "code": 19291,
        "name": "Xã Tân Mỹ",
        "codename": "xa_tan_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1792,
        "code": 19309,
        "name": "Xã Trường Phú",
        "codename": "xa_truong_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1793,
        "code": 19318,
        "name": "Xã Kim Ngân",
        "codename": "xa_kim_ngan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1794,
        "code": 19333,
        "name": "Phường Đông Hà",
        "codename": "phuong_dong_ha",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1795,
        "code": 19351,
        "name": "Phường Nam Đông Hà",
        "codename": "phuong_nam_dong_ha",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1796,
        "code": 19360,
        "name": "Phường Quảng Trị",
        "codename": "phuong_quang_tri",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1797,
        "code": 19363,
        "name": "Xã Vĩnh Linh",
        "codename": "xa_vinh_linh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1798,
        "code": 19366,
        "name": "Xã Bến Quan",
        "codename": "xa_ben_quan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1799,
        "code": 19372,
        "name": "Xã Vĩnh Hoàng",
        "codename": "xa_vinh_hoang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1800,
        "code": 19405,
        "name": "Xã Vĩnh Thủy",
        "codename": "xa_vinh_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1801,
        "code": 19414,
        "name": "Xã Cửa Tùng",
        "codename": "xa_cua_tung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1802,
        "code": 19429,
        "name": "Xã Khe Sanh",
        "codename": "xa_khe_sanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1803,
        "code": 19432,
        "name": "Xã Lao Bảo",
        "codename": "xa_lao_bao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1804,
        "code": 19435,
        "name": "Xã Hướng Lập",
        "codename": "xa_huong_lap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1805,
        "code": 19441,
        "name": "Xã Hướng Phùng",
        "codename": "xa_huong_phung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1806,
        "code": 19462,
        "name": "Xã Tân Lập",
        "codename": "xa_tan_lap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1807,
        "code": 19483,
        "name": "Xã A Dơi",
        "codename": "xa_a_doi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1808,
        "code": 19489,
        "name": "Xã Lìa",
        "codename": "xa_lia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1809,
        "code": 19495,
        "name": "Xã Gio Linh",
        "codename": "xa_gio_linh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1810,
        "code": 19496,
        "name": "Xã Cửa Việt",
        "codename": "xa_cua_viet",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1811,
        "code": 19501,
        "name": "Xã Bến Hải",
        "codename": "xa_ben_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1812,
        "code": 19537,
        "name": "Xã Cồn Tiên",
        "codename": "xa_con_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1813,
        "code": 19555,
        "name": "Xã Hướng Hiệp",
        "codename": "xa_huong_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1814,
        "code": 19564,
        "name": "Xã Đakrông",
        "codename": "xa_dakrong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1815,
        "code": 19567,
        "name": "Xã Ba Lòng",
        "codename": "xa_ba_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1816,
        "code": 19588,
        "name": "Xã Tà Rụt",
        "codename": "xa_ta_rut",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1817,
        "code": 19594,
        "name": "Xã La Lay",
        "codename": "xa_la_lay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1818,
        "code": 19597,
        "name": "Xã Cam Lộ",
        "codename": "xa_cam_lo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1819,
        "code": 19603,
        "name": "Xã Hiếu Giang",
        "codename": "xa_hieu_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1820,
        "code": 19624,
        "name": "Xã Triệu Phong",
        "codename": "xa_trieu_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1821,
        "code": 19639,
        "name": "Xã Nam Cửa Việt",
        "codename": "xa_nam_cua_viet",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1822,
        "code": 19645,
        "name": "Xã Triệu Bình",
        "codename": "xa_trieu_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1823,
        "code": 19654,
        "name": "Xã Triệu Cơ",
        "codename": "xa_trieu_co",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1824,
        "code": 19669,
        "name": "Xã Ái Tử",
        "codename": "xa_ai_tu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1825,
        "code": 19681,
        "name": "Xã Diên Sanh",
        "codename": "xa_dien_sanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1826,
        "code": 19699,
        "name": "Xã Vĩnh Định",
        "codename": "xa_vinh_dinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1827,
        "code": 19702,
        "name": "Xã Hải Lăng",
        "codename": "xa_hai_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1828,
        "code": 19735,
        "name": "Xã Nam Hải Lăng",
        "codename": "xa_nam_hai_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1829,
        "code": 19741,
        "name": "Xã Mỹ Thủy",
        "codename": "xa_my_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1830,
        "code": 19742,
        "name": "Đặc khu Cồn Cỏ",
        "codename": "dac_khu_con_co",
        "division_type": "đặc khu",
        "phone_code": null,
        "parent_code": 44
    },
    {
        "id": 1831,
        "code": 46,
        "name": "Thành phố Huế",
        "codename": "hue",
        "division_type": "thành phố trung ương",
        "phone_code": 234,
        "parent_code": null
    },
    {
        "id": 1832,
        "code": 19753,
        "name": "Phường Phú Xuân",
        "codename": "phuong_phu_xuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1833,
        "code": 19774,
        "name": "Phường Kim Long",
        "codename": "phuong_kim_long",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1834,
        "code": 19777,
        "name": "Phường Vỹ Dạ",
        "codename": "phuong_vy_da",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1835,
        "code": 19789,
        "name": "Phường Thuận Hóa",
        "codename": "phuong_thuan_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1836,
        "code": 19804,
        "name": "Phường Hương An",
        "codename": "phuong_huong_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1837,
        "code": 19813,
        "name": "Phường Thủy Xuân",
        "codename": "phuong_thuy_xuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1838,
        "code": 19815,
        "name": "Phường An Cựu",
        "codename": "phuong_an_cuu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1839,
        "code": 19819,
        "name": "Phường Phong Điền",
        "codename": "phuong_phong_dien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1840,
        "code": 19828,
        "name": "Phường Phong Phú",
        "codename": "phuong_phong_phu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1841,
        "code": 19831,
        "name": "Phường Phong Dinh",
        "codename": "phuong_phong_dinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1842,
        "code": 19858,
        "name": "Phường Phong Thái",
        "codename": "phuong_phong_thai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1843,
        "code": 19867,
        "name": "Xã Quảng Điền",
        "codename": "xa_quang_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1844,
        "code": 19873,
        "name": "Phường Phong Quảng",
        "codename": "phuong_phong_quang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1845,
        "code": 19885,
        "name": "Xã Đan Điền",
        "codename": "xa_dan_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1846,
        "code": 19900,
        "name": "Phường Thuận An",
        "codename": "phuong_thuan_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1847,
        "code": 19909,
        "name": "Phường Dương Nỗ",
        "codename": "phuong_duong_no",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1848,
        "code": 19918,
        "name": "Xã Phú Hồ",
        "codename": "xa_phu_ho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1849,
        "code": 19930,
        "name": "Phường Mỹ Thượng",
        "codename": "phuong_my_thuong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1850,
        "code": 19942,
        "name": "Xã Phú Vang",
        "codename": "xa_phu_vang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1851,
        "code": 19945,
        "name": "Xã Phú Vinh",
        "codename": "xa_phu_vinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1852,
        "code": 19960,
        "name": "Phường Phú Bài",
        "codename": "phuong_phu_bai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1853,
        "code": 19969,
        "name": "Phường Thanh Thủy",
        "codename": "phuong_thanh_thuy",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1854,
        "code": 19975,
        "name": "Phường Hương Thủy",
        "codename": "phuong_huong_thuy",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1855,
        "code": 19996,
        "name": "Phường Hương Trà",
        "codename": "phuong_huong_tra",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1856,
        "code": 20014,
        "name": "Phường Hóa Châu",
        "codename": "phuong_hoa_chau",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1857,
        "code": 20017,
        "name": "Phường Kim Trà",
        "codename": "phuong_kim_tra",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1858,
        "code": 20035,
        "name": "Xã Bình Điền",
        "codename": "xa_binh_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1859,
        "code": 20044,
        "name": "Xã A Lưới 2",
        "codename": "xa_a_luoi_2",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1860,
        "code": 20050,
        "name": "Xã A Lưới 5",
        "codename": "xa_a_luoi_5",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1861,
        "code": 20056,
        "name": "Xã A Lưới 1",
        "codename": "xa_a_luoi_1",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1862,
        "code": 20071,
        "name": "Xã A Lưới 3",
        "codename": "xa_a_luoi_3",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1863,
        "code": 20101,
        "name": "Xã A Lưới 4",
        "codename": "xa_a_luoi_4",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1864,
        "code": 20107,
        "name": "Xã Phú Lộc",
        "codename": "xa_phu_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1865,
        "code": 20122,
        "name": "Xã Vinh Lộc",
        "codename": "xa_vinh_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1866,
        "code": 20131,
        "name": "Xã Hưng Lộc",
        "codename": "xa_hung_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1867,
        "code": 20137,
        "name": "Xã Chân Mây - Lăng Cô",
        "codename": "xa_chan_may_lang_co",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1868,
        "code": 20140,
        "name": "Xã Lộc An",
        "codename": "xa_loc_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1869,
        "code": 20161,
        "name": "Xã Khe Tre",
        "codename": "xa_khe_tre",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1870,
        "code": 20179,
        "name": "Xã Nam Đông",
        "codename": "xa_nam_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1871,
        "code": 20182,
        "name": "Xã Long Quảng",
        "codename": "xa_long_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 46
    },
    {
        "id": 1872,
        "code": 48,
        "name": "Thành phố Đà Nẵng",
        "codename": "da_nang",
        "division_type": "thành phố trung ương",
        "phone_code": 236,
        "parent_code": null
    },
    {
        "id": 1873,
        "code": 20194,
        "name": "Phường Hải Vân",
        "codename": "phuong_hai_van",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1874,
        "code": 20197,
        "name": "Phường Liên Chiểu",
        "codename": "phuong_lien_chieu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1875,
        "code": 20200,
        "name": "Phường Hòa Khánh",
        "codename": "phuong_hoa_khanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1876,
        "code": 20209,
        "name": "Phường Thanh Khê",
        "codename": "phuong_thanh_khe",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1877,
        "code": 20242,
        "name": "Phường Hải Châu",
        "codename": "phuong_hai_chau",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1878,
        "code": 20257,
        "name": "Phường Hòa Cường",
        "codename": "phuong_hoa_cuong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1879,
        "code": 20260,
        "name": "Phường Cẩm Lệ",
        "codename": "phuong_cam_le",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1880,
        "code": 20263,
        "name": "Phường Sơn Trà",
        "codename": "phuong_son_tra",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1881,
        "code": 20275,
        "name": "Phường An Hải",
        "codename": "phuong_an_hai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1882,
        "code": 20285,
        "name": "Phường Ngũ Hành Sơn",
        "codename": "phuong_ngu_hanh_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1883,
        "code": 20305,
        "name": "Phường An Khê",
        "codename": "phuong_an_khe",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1884,
        "code": 20308,
        "name": "Xã Bà Nà",
        "codename": "xa_ba_na",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1885,
        "code": 20314,
        "name": "Phường Hòa Xuân",
        "codename": "phuong_hoa_xuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1886,
        "code": 20320,
        "name": "Xã Hòa Vang",
        "codename": "xa_hoa_vang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1887,
        "code": 20332,
        "name": "Xã Hòa Tiến",
        "codename": "xa_hoa_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1888,
        "code": 20333,
        "name": "Đặc khu Hoàng Sa",
        "codename": "dac_khu_hoang_sa",
        "division_type": "đặc khu",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1889,
        "code": 20335,
        "name": "Phường Bàn Thạch",
        "codename": "phuong_ban_thach",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1890,
        "code": 20341,
        "name": "Phường Tam Kỳ",
        "codename": "phuong_tam_ky",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1891,
        "code": 20350,
        "name": "Phường Hương Trà",
        "codename": "phuong_huong_tra",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1892,
        "code": 20356,
        "name": "Phường Quảng Phú",
        "codename": "phuong_quang_phu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1893,
        "code": 20364,
        "name": "Xã Chiên Đàn",
        "codename": "xa_chien_dan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1894,
        "code": 20380,
        "name": "Xã Tây Hồ",
        "codename": "xa_tay_ho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1895,
        "code": 20392,
        "name": "Xã Phú Ninh",
        "codename": "xa_phu_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1896,
        "code": 20401,
        "name": "Phường Hội An Tây",
        "codename": "phuong_hoi_an_tay",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1897,
        "code": 20410,
        "name": "Phường Hội An",
        "codename": "phuong_hoi_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1898,
        "code": 20413,
        "name": "Phường Hội An Đông",
        "codename": "phuong_hoi_an_dong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1899,
        "code": 20434,
        "name": "Xã Tân Hiệp",
        "codename": "xa_tan_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1900,
        "code": 20443,
        "name": "Xã Hùng Sơn",
        "codename": "xa_hung_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1901,
        "code": 20455,
        "name": "Xã Tây Giang",
        "codename": "xa_tay_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1902,
        "code": 20458,
        "name": "Xã Avương",
        "codename": "xa_avuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1903,
        "code": 20467,
        "name": "Xã Đông Giang",
        "codename": "xa_dong_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1904,
        "code": 20476,
        "name": "Xã Sông Kôn",
        "codename": "xa_song_kon",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1905,
        "code": 20485,
        "name": "Xã Sông Vàng",
        "codename": "xa_song_vang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1906,
        "code": 20494,
        "name": "Xã Bến Hiên",
        "codename": "xa_ben_hien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1907,
        "code": 20500,
        "name": "Xã Đại Lộc",
        "codename": "xa_dai_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1908,
        "code": 20506,
        "name": "Xã Thượng Đức",
        "codename": "xa_thuong_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1909,
        "code": 20515,
        "name": "Xã Hà Nha",
        "codename": "xa_ha_nha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1910,
        "code": 20539,
        "name": "Xã Vu Gia",
        "codename": "xa_vu_gia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1911,
        "code": 20542,
        "name": "Xã Phú Thuận",
        "codename": "xa_phu_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1912,
        "code": 20551,
        "name": "Phường Điện Bàn",
        "codename": "phuong_dien_ban",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1913,
        "code": 20557,
        "name": "Phường Điện Bàn Bắc",
        "codename": "phuong_dien_ban_bac",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1914,
        "code": 20569,
        "name": "Xã Điện Bàn Tây",
        "codename": "xa_dien_ban_tay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1915,
        "code": 20575,
        "name": "Phường An Thắng",
        "codename": "phuong_an_thang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1916,
        "code": 20579,
        "name": "Phường Điện Bàn Đông",
        "codename": "phuong_dien_ban_dong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1917,
        "code": 20587,
        "name": "Xã Gò Nổi",
        "codename": "xa_go_noi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1918,
        "code": 20599,
        "name": "Xã Nam Phước",
        "codename": "xa_nam_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1919,
        "code": 20611,
        "name": "Xã Thu Bồn",
        "codename": "xa_thu_bon",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1920,
        "code": 20623,
        "name": "Xã Duy Xuyên",
        "codename": "xa_duy_xuyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1921,
        "code": 20635,
        "name": "Xã Duy Nghĩa",
        "codename": "xa_duy_nghia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1922,
        "code": 20641,
        "name": "Xã Quế Sơn",
        "codename": "xa_que_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1923,
        "code": 20650,
        "name": "Xã Xuân Phú",
        "codename": "xa_xuan_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1924,
        "code": 20656,
        "name": "Xã Nông Sơn",
        "codename": "xa_nong_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1925,
        "code": 20662,
        "name": "Xã Quế Sơn Trung",
        "codename": "xa_que_son_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1926,
        "code": 20669,
        "name": "Xã Quế Phước",
        "codename": "xa_que_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1927,
        "code": 20695,
        "name": "Xã Thạnh Mỹ",
        "codename": "xa_thanh_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1928,
        "code": 20698,
        "name": "Xã La Êê",
        "codename": "xa_la_ee",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1929,
        "code": 20704,
        "name": "Xã La Dêê",
        "codename": "xa_la_dee",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1930,
        "code": 20707,
        "name": "Xã Nam Giang",
        "codename": "xa_nam_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1931,
        "code": 20710,
        "name": "Xã Bến Giằng",
        "codename": "xa_ben_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1932,
        "code": 20716,
        "name": "Xã Đắc Pring",
        "codename": "xa_dac_pring",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1933,
        "code": 20722,
        "name": "Xã Khâm Đức",
        "codename": "xa_kham_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1934,
        "code": 20728,
        "name": "Xã Phước Hiệp",
        "codename": "xa_phuoc_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1935,
        "code": 20734,
        "name": "Xã Phước Năng",
        "codename": "xa_phuoc_nang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1936,
        "code": 20740,
        "name": "Xã Phước Chánh",
        "codename": "xa_phuoc_chanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1937,
        "code": 20752,
        "name": "Xã Phước Thành",
        "codename": "xa_phuoc_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1938,
        "code": 20767,
        "name": "Xã Việt An",
        "codename": "xa_viet_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1939,
        "code": 20770,
        "name": "Xã Phước Trà",
        "codename": "xa_phuoc_tra",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1940,
        "code": 20779,
        "name": "Xã Hiệp Đức",
        "codename": "xa_hiep_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1941,
        "code": 20791,
        "name": "Xã Thăng Bình",
        "codename": "xa_thang_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1942,
        "code": 20794,
        "name": "Xã Thăng An",
        "codename": "xa_thang_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1943,
        "code": 20818,
        "name": "Xã Đồng Dương",
        "codename": "xa_dong_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1944,
        "code": 20827,
        "name": "Xã Thăng Phú",
        "codename": "xa_thang_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1945,
        "code": 20836,
        "name": "Xã Thăng Trường",
        "codename": "xa_thang_truong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1946,
        "code": 20848,
        "name": "Xã Thăng Điền",
        "codename": "xa_thang_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1947,
        "code": 20854,
        "name": "Xã Tiên Phước",
        "codename": "xa_tien_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1948,
        "code": 20857,
        "name": "Xã Sơn Cẩm Hà",
        "codename": "xa_son_cam_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1949,
        "code": 20875,
        "name": "Xã Lãnh Ngọc",
        "codename": "xa_lanh_ngoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1950,
        "code": 20878,
        "name": "Xã Thạnh Bình",
        "codename": "xa_thanh_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1951,
        "code": 20900,
        "name": "Xã Trà My",
        "codename": "xa_tra_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1952,
        "code": 20908,
        "name": "Xã Trà Liên",
        "codename": "xa_tra_lien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1953,
        "code": 20920,
        "name": "Xã Trà Đốc",
        "codename": "xa_tra_doc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1954,
        "code": 20923,
        "name": "Xã Trà Tân",
        "codename": "xa_tra_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1955,
        "code": 20929,
        "name": "Xã Trà Giáp",
        "codename": "xa_tra_giap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1956,
        "code": 20938,
        "name": "Xã Trà Leng",
        "codename": "xa_tra_leng",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1957,
        "code": 20941,
        "name": "Xã Trà Tập",
        "codename": "xa_tra_tap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1958,
        "code": 20944,
        "name": "Xã Nam Trà My",
        "codename": "xa_nam_tra_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1959,
        "code": 20950,
        "name": "Xã Trà Linh",
        "codename": "xa_tra_linh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1960,
        "code": 20959,
        "name": "Xã Trà Vân",
        "codename": "xa_tra_van",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1961,
        "code": 20965,
        "name": "Xã Núi Thành",
        "codename": "xa_nui_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1962,
        "code": 20971,
        "name": "Xã Tam Xuân",
        "codename": "xa_tam_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1963,
        "code": 20977,
        "name": "Xã Đức Phú",
        "codename": "xa_duc_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1964,
        "code": 20984,
        "name": "Xã Tam Anh",
        "codename": "xa_tam_anh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1965,
        "code": 20992,
        "name": "Xã Tam Hải",
        "codename": "xa_tam_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1966,
        "code": 21004,
        "name": "Xã Tam Mỹ",
        "codename": "xa_tam_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 48
    },
    {
        "id": 1967,
        "code": 51,
        "name": "Tỉnh Quảng Ngãi",
        "codename": "quang_ngai",
        "division_type": "tỉnh",
        "phone_code": 255,
        "parent_code": null
    },
    {
        "id": 1968,
        "code": 21025,
        "name": "Phường Cẩm Thành",
        "codename": "phuong_cam_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1969,
        "code": 21028,
        "name": "Phường Nghĩa Lộ",
        "codename": "phuong_nghia_lo",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1970,
        "code": 21034,
        "name": "Xã An Phú",
        "codename": "xa_an_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1971,
        "code": 21040,
        "name": "Xã Bình Sơn",
        "codename": "xa_binh_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1972,
        "code": 21061,
        "name": "Xã Vạn Tường",
        "codename": "xa_van_tuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1973,
        "code": 21085,
        "name": "Xã Bình Minh",
        "codename": "xa_binh_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1974,
        "code": 21100,
        "name": "Xã Bình Chương",
        "codename": "xa_binh_chuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1975,
        "code": 21109,
        "name": "Xã Đông Sơn",
        "codename": "xa_dong_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1976,
        "code": 21115,
        "name": "Xã Trà Bồng",
        "codename": "xa_tra_bong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1977,
        "code": 21124,
        "name": "Xã Thanh Bồng",
        "codename": "xa_thanh_bong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1978,
        "code": 21127,
        "name": "Xã Đông Trà Bồng",
        "codename": "xa_dong_tra_bong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1979,
        "code": 21136,
        "name": "Xã Cà Đam",
        "codename": "xa_ca_dam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1980,
        "code": 21154,
        "name": "Xã Tây Trà",
        "codename": "xa_tay_tra",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1981,
        "code": 21157,
        "name": "Xã Tây Trà Bồng",
        "codename": "xa_tay_tra_bong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1982,
        "code": 21172,
        "name": "Phường Trương Quang Trọng",
        "codename": "phuong_truong_quang_trong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1983,
        "code": 21181,
        "name": "Xã Thọ Phong",
        "codename": "xa_tho_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1984,
        "code": 21196,
        "name": "Xã Trường Giang",
        "codename": "xa_truong_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1985,
        "code": 21205,
        "name": "Xã Ba Gia",
        "codename": "xa_ba_gia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1986,
        "code": 21211,
        "name": "Xã Tịnh Khê",
        "codename": "xa_tinh_khe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1987,
        "code": 21220,
        "name": "Xã Sơn Tịnh",
        "codename": "xa_son_tinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1988,
        "code": 21235,
        "name": "Xã Tư Nghĩa",
        "codename": "xa_tu_nghia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1989,
        "code": 21238,
        "name": "Xã Vệ Giang",
        "codename": "xa_ve_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1990,
        "code": 21244,
        "name": "Xã Trà Giang",
        "codename": "xa_tra_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1991,
        "code": 21250,
        "name": "Xã Nghĩa Giang",
        "codename": "xa_nghia_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1992,
        "code": 21289,
        "name": "Xã Sơn Hà",
        "codename": "xa_son_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1993,
        "code": 21292,
        "name": "Xã Sơn Hạ",
        "codename": "xa_son_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1994,
        "code": 21307,
        "name": "Xã Sơn Linh",
        "codename": "xa_son_linh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1995,
        "code": 21319,
        "name": "Xã Sơn Thủy",
        "codename": "xa_son_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1996,
        "code": 21325,
        "name": "Xã Sơn Kỳ",
        "codename": "xa_son_ky",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1997,
        "code": 21334,
        "name": "Xã Sơn Tây Thượng",
        "codename": "xa_son_tay_thuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1998,
        "code": 21340,
        "name": "Xã Sơn Tây",
        "codename": "xa_son_tay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 1999,
        "code": 21343,
        "name": "Xã Sơn Tây Hạ",
        "codename": "xa_son_tay_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2000,
        "code": 21349,
        "name": "Xã Sơn Mai",
        "codename": "xa_son_mai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2001,
        "code": 21361,
        "name": "Xã Minh Long",
        "codename": "xa_minh_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2002,
        "code": 21364,
        "name": "Xã Nghĩa Hành",
        "codename": "xa_nghia_hanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2003,
        "code": 21370,
        "name": "Xã Phước Giang",
        "codename": "xa_phuoc_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2004,
        "code": 21385,
        "name": "Xã Đình Cương",
        "codename": "xa_dinh_cuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2005,
        "code": 21388,
        "name": "Xã Thiện Tín",
        "codename": "xa_thien_tin",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2006,
        "code": 21400,
        "name": "Xã Mộ Đức",
        "codename": "xa_mo_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2007,
        "code": 21409,
        "name": "Xã Long Phụng",
        "codename": "xa_long_phung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2008,
        "code": 21421,
        "name": "Xã Mỏ Cày",
        "codename": "xa_mo_cay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2009,
        "code": 21433,
        "name": "Xã Lân Phong",
        "codename": "xa_lan_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2010,
        "code": 21439,
        "name": "Phường Đức Phổ",
        "codename": "phuong_duc_pho",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2011,
        "code": 21451,
        "name": "Phường Trà Câu",
        "codename": "phuong_tra_cau",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2012,
        "code": 21457,
        "name": "Xã Nguyễn Nghiêm",
        "codename": "xa_nguyen_nghiem",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2013,
        "code": 21472,
        "name": "Xã Khánh Cường",
        "codename": "xa_khanh_cuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2014,
        "code": 21478,
        "name": "Phường Sa Huỳnh",
        "codename": "phuong_sa_huynh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2015,
        "code": 21484,
        "name": "Xã Ba Tơ",
        "codename": "xa_ba_to",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2016,
        "code": 21490,
        "name": "Xã Ba Vinh",
        "codename": "xa_ba_vinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2017,
        "code": 21496,
        "name": "Xã Ba Động",
        "codename": "xa_ba_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2018,
        "code": 21499,
        "name": "Xã Ba Dinh",
        "codename": "xa_ba_dinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2019,
        "code": 21520,
        "name": "Xã Đặng Thùy Trâm",
        "codename": "xa_dang_thuy_tram",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2020,
        "code": 21523,
        "name": "Xã Ba Tô",
        "codename": "xa_ba_to",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2021,
        "code": 21529,
        "name": "Xã Ba Vì",
        "codename": "xa_ba_vi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2022,
        "code": 21538,
        "name": "Xã Ba Xa",
        "codename": "xa_ba_xa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2023,
        "code": 21548,
        "name": "Đặc khu Lý Sơn",
        "codename": "dac_khu_ly_son",
        "division_type": "đặc khu",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2024,
        "code": 23284,
        "name": "Phường Đăk Cấm",
        "codename": "phuong_dak_cam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2025,
        "code": 23293,
        "name": "Phường Kon Tum",
        "codename": "phuong_kon_tum",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2026,
        "code": 23302,
        "name": "Phường Đăk Bla",
        "codename": "phuong_dak_bla",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2027,
        "code": 23317,
        "name": "Xã Ngọk Bay",
        "codename": "xa_ngok_bay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2028,
        "code": 23326,
        "name": "Xã Ia Chim",
        "codename": "xa_ia_chim",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2029,
        "code": 23332,
        "name": "Xã Đăk Rơ Wa",
        "codename": "xa_dak_ro_wa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2030,
        "code": 23341,
        "name": "Xã Đăk Pék",
        "codename": "xa_dak_pek",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2031,
        "code": 23344,
        "name": "Xã Đăk Plô",
        "codename": "xa_dak_plo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2032,
        "code": 23356,
        "name": "Xã Xốp",
        "codename": "xa_xop",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2033,
        "code": 23365,
        "name": "Xã Ngọc Linh",
        "codename": "xa_ngoc_linh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2034,
        "code": 23368,
        "name": "Xã Đăk Long",
        "codename": "xa_dak_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2035,
        "code": 23374,
        "name": "Xã Đăk Môn",
        "codename": "xa_dak_mon",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2036,
        "code": 23377,
        "name": "Xã Bờ Y",
        "codename": "xa_bo_y",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2037,
        "code": 23383,
        "name": "Xã Dục Nông",
        "codename": "xa_duc_nong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2038,
        "code": 23392,
        "name": "Xã Sa Loong",
        "codename": "xa_sa_loong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2039,
        "code": 23401,
        "name": "Xã Đăk Tô",
        "codename": "xa_dak_to",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2040,
        "code": 23416,
        "name": "Xã Đăk Sao",
        "codename": "xa_dak_sao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2041,
        "code": 23419,
        "name": "Xã Đăk Tờ Kan",
        "codename": "xa_dak_to_kan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2042,
        "code": 23425,
        "name": "Xã Tu Mơ Rông",
        "codename": "xa_tu_mo_rong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2043,
        "code": 23428,
        "name": "Xã Ngọk Tụ",
        "codename": "xa_ngok_tu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2044,
        "code": 23430,
        "name": "Xã Kon Đào",
        "codename": "xa_kon_dao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2045,
        "code": 23446,
        "name": "Xã Măng Ri",
        "codename": "xa_mang_ri",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2046,
        "code": 23455,
        "name": "Xã Măng Bút",
        "codename": "xa_mang_but",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2047,
        "code": 23473,
        "name": "Xã Măng Đen",
        "codename": "xa_mang_den",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2048,
        "code": 23476,
        "name": "Xã Kon Plông",
        "codename": "xa_kon_plong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2049,
        "code": 23479,
        "name": "Xã Đăk Rve",
        "codename": "xa_dak_rve",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2050,
        "code": 23485,
        "name": "Xã Đăk Kôi",
        "codename": "xa_dak_koi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2051,
        "code": 23497,
        "name": "Xã Kon Braih",
        "codename": "xa_kon_braih",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2052,
        "code": 23500,
        "name": "Xã Đăk Hà",
        "codename": "xa_dak_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2053,
        "code": 23504,
        "name": "Xã Đăk Pxi",
        "codename": "xa_dak_pxi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2054,
        "code": 23510,
        "name": "Xã Đăk Ui",
        "codename": "xa_dak_ui",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2055,
        "code": 23512,
        "name": "Xã Đăk Mar",
        "codename": "xa_dak_mar",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2056,
        "code": 23515,
        "name": "Xã Ngọk Réo",
        "codename": "xa_ngok_reo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2057,
        "code": 23527,
        "name": "Xã Sa Thầy",
        "codename": "xa_sa_thay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2058,
        "code": 23530,
        "name": "Xã Rờ Kơi",
        "codename": "xa_ro_koi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2059,
        "code": 23534,
        "name": "Xã Sa Bình",
        "codename": "xa_sa_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2060,
        "code": 23535,
        "name": "Xã Ia Đal",
        "codename": "xa_ia_dal",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2061,
        "code": 23536,
        "name": "Xã Mô Rai",
        "codename": "xa_mo_rai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2062,
        "code": 23538,
        "name": "Xã Ia Tơi",
        "codename": "xa_ia_toi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2063,
        "code": 23548,
        "name": "Xã Ya Ly",
        "codename": "xa_ya_ly",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 51
    },
    {
        "id": 2064,
        "code": 52,
        "name": "Tỉnh Gia Lai",
        "codename": "gia_lai",
        "division_type": "tỉnh",
        "phone_code": 269,
        "parent_code": null
    },
    {
        "id": 2065,
        "code": 21553,
        "name": "Phường Quy Nhơn Bắc",
        "codename": "phuong_quy_nhon_bac",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2066,
        "code": 21583,
        "name": "Phường Quy Nhơn",
        "codename": "phuong_quy_nhon",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2067,
        "code": 21589,
        "name": "Phường Quy Nhơn Tây",
        "codename": "phuong_quy_nhon_tay",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2068,
        "code": 21592,
        "name": "Phường Quy Nhơn Nam",
        "codename": "phuong_quy_nhon_nam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2069,
        "code": 21601,
        "name": "Phường Quy Nhơn Đông",
        "codename": "phuong_quy_nhon_dong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2070,
        "code": 21607,
        "name": "Xã Nhơn Châu",
        "codename": "xa_nhon_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2071,
        "code": 21609,
        "name": "Xã An Lão",
        "codename": "xa_an_lao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2072,
        "code": 21616,
        "name": "Xã An Vinh",
        "codename": "xa_an_vinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2073,
        "code": 21622,
        "name": "Xã An Toàn",
        "codename": "xa_an_toan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2074,
        "code": 21628,
        "name": "Xã An Hòa",
        "codename": "xa_an_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2075,
        "code": 21637,
        "name": "Phường Tam Quan",
        "codename": "phuong_tam_quan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2076,
        "code": 21640,
        "name": "Phường Bồng Sơn",
        "codename": "phuong_bong_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2077,
        "code": 21655,
        "name": "Phường Hoài Nhơn Bắc",
        "codename": "phuong_hoai_nhon_bac",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2078,
        "code": 21661,
        "name": "Phường Hoài Nhơn Tây",
        "codename": "phuong_hoai_nhon_tay",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2079,
        "code": 21664,
        "name": "Phường Hoài Nhơn",
        "codename": "phuong_hoai_nhon",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2080,
        "code": 21670,
        "name": "Phường Hoài Nhơn Đông",
        "codename": "phuong_hoai_nhon_dong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2081,
        "code": 21673,
        "name": "Phường Hoài Nhơn Nam",
        "codename": "phuong_hoai_nhon_nam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2082,
        "code": 21688,
        "name": "Xã Hoài Ân",
        "codename": "xa_hoai_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2083,
        "code": 21697,
        "name": "Xã Ân Hảo",
        "codename": "xa_an_hao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2084,
        "code": 21703,
        "name": "Xã Vạn Đức",
        "codename": "xa_van_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2085,
        "code": 21715,
        "name": "Xã Ân Tường",
        "codename": "xa_an_tuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2086,
        "code": 21727,
        "name": "Xã Kim Sơn",
        "codename": "xa_kim_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2087,
        "code": 21730,
        "name": "Xã Phù Mỹ",
        "codename": "xa_phu_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2088,
        "code": 21733,
        "name": "Xã Bình Dương",
        "codename": "xa_binh_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2089,
        "code": 21739,
        "name": "Xã Phù Mỹ Bắc",
        "codename": "xa_phu_my_bac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2090,
        "code": 21751,
        "name": "Xã Phù Mỹ Đông",
        "codename": "xa_phu_my_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2091,
        "code": 21757,
        "name": "Xã Phù Mỹ Tây",
        "codename": "xa_phu_my_tay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2092,
        "code": 21769,
        "name": "Xã An Lương",
        "codename": "xa_an_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2093,
        "code": 21775,
        "name": "Xã Phù Mỹ Nam",
        "codename": "xa_phu_my_nam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2094,
        "code": 21786,
        "name": "Xã Vĩnh Thạnh",
        "codename": "xa_vinh_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2095,
        "code": 21787,
        "name": "Xã Vĩnh Sơn",
        "codename": "xa_vinh_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2096,
        "code": 21796,
        "name": "Xã Vĩnh Thịnh",
        "codename": "xa_vinh_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2097,
        "code": 21805,
        "name": "Xã Vĩnh Quang",
        "codename": "xa_vinh_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2098,
        "code": 21808,
        "name": "Xã Tây Sơn",
        "codename": "xa_tay_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2099,
        "code": 21817,
        "name": "Xã Bình Hiệp",
        "codename": "xa_binh_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2100,
        "code": 21820,
        "name": "Xã Bình Khê",
        "codename": "xa_binh_khe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2101,
        "code": 21829,
        "name": "Xã Bình An",
        "codename": "xa_binh_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2102,
        "code": 21835,
        "name": "Xã Bình Phú",
        "codename": "xa_binh_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2103,
        "code": 21853,
        "name": "Xã Phù Cát",
        "codename": "xa_phu_cat",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2104,
        "code": 21859,
        "name": "Xã Đề Gi",
        "codename": "xa_de_gi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2105,
        "code": 21868,
        "name": "Xã Hội Sơn",
        "codename": "xa_hoi_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2106,
        "code": 21871,
        "name": "Xã Hòa Hội",
        "codename": "xa_hoa_hoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2107,
        "code": 21880,
        "name": "Xã Cát Tiến",
        "codename": "xa_cat_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2108,
        "code": 21892,
        "name": "Xã Xuân An",
        "codename": "xa_xuan_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2109,
        "code": 21901,
        "name": "Xã Ngô Mây",
        "codename": "xa_ngo_may",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2110,
        "code": 21907,
        "name": "Phường Bình Định",
        "codename": "phuong_binh_dinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2111,
        "code": 21910,
        "name": "Phường An Nhơn",
        "codename": "phuong_an_nhon",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2112,
        "code": 21925,
        "name": "Phường An Nhơn Bắc",
        "codename": "phuong_an_nhon_bac",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2113,
        "code": 21934,
        "name": "Phường An Nhơn Đông",
        "codename": "phuong_an_nhon_dong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2114,
        "code": 21940,
        "name": "Xã An Nhơn Tây",
        "codename": "xa_an_nhon_tay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2115,
        "code": 21943,
        "name": "Phường An Nhơn Nam",
        "codename": "phuong_an_nhon_nam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2116,
        "code": 21952,
        "name": "Xã Tuy Phước",
        "codename": "xa_tuy_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2117,
        "code": 21964,
        "name": "Xã Tuy Phước Bắc",
        "codename": "xa_tuy_phuoc_bac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2118,
        "code": 21970,
        "name": "Xã Tuy Phước Đông",
        "codename": "xa_tuy_phuoc_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2119,
        "code": 21985,
        "name": "Xã Tuy Phước Tây",
        "codename": "xa_tuy_phuoc_tay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2120,
        "code": 21994,
        "name": "Xã Vân Canh",
        "codename": "xa_van_canh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2121,
        "code": 21997,
        "name": "Xã Canh Liên",
        "codename": "xa_canh_lien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2122,
        "code": 22006,
        "name": "Xã Canh Vinh",
        "codename": "xa_canh_vinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2123,
        "code": 23563,
        "name": "Phường Diên Hồng",
        "codename": "phuong_dien_hong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2124,
        "code": 23575,
        "name": "Phường Pleiku",
        "codename": "phuong_pleiku",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2125,
        "code": 23584,
        "name": "Phường Thống Nhất",
        "codename": "phuong_thong_nhat",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2126,
        "code": 23586,
        "name": "Phường Hội Phú",
        "codename": "phuong_hoi_phu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2127,
        "code": 23590,
        "name": "Xã Biển Hồ",
        "codename": "xa_bien_ho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2128,
        "code": 23602,
        "name": "Phường An Phú",
        "codename": "phuong_an_phu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2129,
        "code": 23611,
        "name": "Xã Gào",
        "codename": "xa_gao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2130,
        "code": 23614,
        "name": "Phường An Bình",
        "codename": "phuong_an_binh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2131,
        "code": 23617,
        "name": "Phường An Khê",
        "codename": "phuong_an_khe",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2132,
        "code": 23629,
        "name": "Xã Cửu An",
        "codename": "xa_cuu_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2133,
        "code": 23638,
        "name": "Xã Kbang",
        "codename": "xa_kbang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2134,
        "code": 23644,
        "name": "Xã Đak Rong",
        "codename": "xa_dak_rong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2135,
        "code": 23647,
        "name": "Xã Sơn Lang",
        "codename": "xa_son_lang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2136,
        "code": 23650,
        "name": "Xã Krong",
        "codename": "xa_krong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2137,
        "code": 23668,
        "name": "Xã Tơ Tung",
        "codename": "xa_to_tung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2138,
        "code": 23674,
        "name": "Xã Kông Bơ La",
        "codename": "xa_kong_bo_la",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2139,
        "code": 23677,
        "name": "Xã Đak Đoa",
        "codename": "xa_dak_doa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2140,
        "code": 23683,
        "name": "Xã Đak Sơmei",
        "codename": "xa_dak_somei",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2141,
        "code": 23701,
        "name": "Xã Kon Gang",
        "codename": "xa_kon_gang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2142,
        "code": 23710,
        "name": "Xã Ia Băng",
        "codename": "xa_ia_bang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2143,
        "code": 23714,
        "name": "Xã KDang",
        "codename": "xa_kdang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2144,
        "code": 23722,
        "name": "Xã Chư Păh",
        "codename": "xa_chu_pah",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2145,
        "code": 23728,
        "name": "Xã Ia Khươl",
        "codename": "xa_ia_khuol",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2146,
        "code": 23734,
        "name": "Xã Ia Ly",
        "codename": "xa_ia_ly",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2147,
        "code": 23749,
        "name": "Xã Ia Phí",
        "codename": "xa_ia_phi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2148,
        "code": 23764,
        "name": "Xã Ia Grai",
        "codename": "xa_ia_grai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2149,
        "code": 23767,
        "name": "Xã Ia Hrung",
        "codename": "xa_ia_hrung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2150,
        "code": 23776,
        "name": "Xã Ia Krái",
        "codename": "xa_ia_krai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2151,
        "code": 23782,
        "name": "Xã Ia O",
        "codename": "xa_ia_o",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2152,
        "code": 23788,
        "name": "Xã Ia Chia",
        "codename": "xa_ia_chia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2153,
        "code": 23794,
        "name": "Xã Mang Yang",
        "codename": "xa_mang_yang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2154,
        "code": 23798,
        "name": "Xã Ayun",
        "codename": "xa_ayun",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2155,
        "code": 23799,
        "name": "Xã Hra",
        "codename": "xa_hra",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2156,
        "code": 23812,
        "name": "Xã Lơ Pang",
        "codename": "xa_lo_pang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2157,
        "code": 23818,
        "name": "Xã Kon Chiêng",
        "codename": "xa_kon_chieng",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2158,
        "code": 23824,
        "name": "Xã Kông Chro",
        "codename": "xa_kong_chro",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2159,
        "code": 23830,
        "name": "Xã Chư Krey",
        "codename": "xa_chu_krey",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2160,
        "code": 23833,
        "name": "Xã Ya Ma",
        "codename": "xa_ya_ma",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2161,
        "code": 23839,
        "name": "Xã SRó",
        "codename": "xa_sro",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2162,
        "code": 23842,
        "name": "Xã Đăk Song",
        "codename": "xa_dak_song",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2163,
        "code": 23851,
        "name": "Xã Chơ Long",
        "codename": "xa_cho_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2164,
        "code": 23857,
        "name": "Xã Đức Cơ",
        "codename": "xa_duc_co",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2165,
        "code": 23866,
        "name": "Xã Ia Krêl",
        "codename": "xa_ia_krel",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2166,
        "code": 23869,
        "name": "Xã Ia Dơk",
        "codename": "xa_ia_dok",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2167,
        "code": 23872,
        "name": "Xã Ia Dom",
        "codename": "xa_ia_dom",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2168,
        "code": 23881,
        "name": "Xã Ia Pnôn",
        "codename": "xa_ia_pnon",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2169,
        "code": 23884,
        "name": "Xã Ia Nan",
        "codename": "xa_ia_nan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2170,
        "code": 23887,
        "name": "Xã Chư Prông",
        "codename": "xa_chu_prong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2171,
        "code": 23896,
        "name": "Xã Bàu Cạn",
        "codename": "xa_bau_can",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2172,
        "code": 23908,
        "name": "Xã Ia Tôr",
        "codename": "xa_ia_tor",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2173,
        "code": 23911,
        "name": "Xã Ia Boòng",
        "codename": "xa_ia_boong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2174,
        "code": 23917,
        "name": "Xã Ia Púch",
        "codename": "xa_ia_puch",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2175,
        "code": 23926,
        "name": "Xã Ia Pia",
        "codename": "xa_ia_pia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2176,
        "code": 23935,
        "name": "Xã Ia Lâu",
        "codename": "xa_ia_lau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2177,
        "code": 23938,
        "name": "Xã Ia Mơ",
        "codename": "xa_ia_mo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2178,
        "code": 23941,
        "name": "Xã Chư Sê",
        "codename": "xa_chu_se",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2179,
        "code": 23942,
        "name": "Xã Chư Pưh",
        "codename": "xa_chu_puh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2180,
        "code": 23947,
        "name": "Xã Bờ Ngoong",
        "codename": "xa_bo_ngoong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2181,
        "code": 23954,
        "name": "Xã Al Bá",
        "codename": "xa_al_ba",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2182,
        "code": 23971,
        "name": "Xã Ia Hrú",
        "codename": "xa_ia_hru",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2183,
        "code": 23977,
        "name": "Xã Ia Ko",
        "codename": "xa_ia_ko",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2184,
        "code": 23986,
        "name": "Xã Ia Le",
        "codename": "xa_ia_le",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2185,
        "code": 23995,
        "name": "Xã Đak Pơ",
        "codename": "xa_dak_po",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2186,
        "code": 24007,
        "name": "Xã Ya Hội",
        "codename": "xa_ya_hoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2187,
        "code": 24013,
        "name": "Xã Pờ Tó",
        "codename": "xa_po_to",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2188,
        "code": 24022,
        "name": "Xã Ia Pa",
        "codename": "xa_ia_pa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2189,
        "code": 24028,
        "name": "Xã Ia Tul",
        "codename": "xa_ia_tul",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2190,
        "code": 24043,
        "name": "Xã Phú Thiện",
        "codename": "xa_phu_thien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2191,
        "code": 24044,
        "name": "Phường Ayun Pa",
        "codename": "phuong_ayun_pa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2192,
        "code": 24049,
        "name": "Xã Chư A Thai",
        "codename": "xa_chu_a_thai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2193,
        "code": 24061,
        "name": "Xã Ia Hiao",
        "codename": "xa_ia_hiao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2194,
        "code": 24065,
        "name": "Xã Ia Rbol",
        "codename": "xa_ia_rbol",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2195,
        "code": 24073,
        "name": "Xã Ia Sao",
        "codename": "xa_ia_sao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2196,
        "code": 24076,
        "name": "Xã Phú Túc",
        "codename": "xa_phu_tuc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2197,
        "code": 24100,
        "name": "Xã Ia Dreh",
        "codename": "xa_ia_dreh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2198,
        "code": 24109,
        "name": "Xã Uar",
        "codename": "xa_uar",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2199,
        "code": 24112,
        "name": "Xã Ia Rsai",
        "codename": "xa_ia_rsai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 52
    },
    {
        "id": 2200,
        "code": 56,
        "name": "Tỉnh Khánh Hòa",
        "codename": "khanh_hoa",
        "division_type": "tỉnh",
        "phone_code": 258,
        "parent_code": null
    },
    {
        "id": 2201,
        "code": 22333,
        "name": "Phường Bắc Nha Trang",
        "codename": "phuong_bac_nha_trang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2202,
        "code": 22366,
        "name": "Phường Nha Trang",
        "codename": "phuong_nha_trang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2203,
        "code": 22390,
        "name": "Phường Tây Nha Trang",
        "codename": "phuong_tay_nha_trang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2204,
        "code": 22402,
        "name": "Phường Nam Nha Trang",
        "codename": "phuong_nam_nha_trang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2205,
        "code": 22411,
        "name": "Phường Bắc Cam Ranh",
        "codename": "phuong_bac_cam_ranh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2206,
        "code": 22420,
        "name": "Phường Cam Ranh",
        "codename": "phuong_cam_ranh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2207,
        "code": 22423,
        "name": "Phường Ba Ngòi",
        "codename": "phuong_ba_ngoi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2208,
        "code": 22432,
        "name": "Phường Cam Linh",
        "codename": "phuong_cam_linh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2209,
        "code": 22435,
        "name": "Xã Cam Hiệp",
        "codename": "xa_cam_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2210,
        "code": 22453,
        "name": "Xã Cam Lâm",
        "codename": "xa_cam_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2211,
        "code": 22465,
        "name": "Xã Cam An",
        "codename": "xa_cam_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2212,
        "code": 22480,
        "name": "Xã Nam Cam Ranh",
        "codename": "xa_nam_cam_ranh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2213,
        "code": 22489,
        "name": "Xã Vạn Ninh",
        "codename": "xa_van_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2214,
        "code": 22498,
        "name": "Xã Tu Bông",
        "codename": "xa_tu_bong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2215,
        "code": 22504,
        "name": "Xã Đại Lãnh",
        "codename": "xa_dai_lanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2216,
        "code": 22516,
        "name": "Xã Vạn Thắng",
        "codename": "xa_van_thang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2217,
        "code": 22525,
        "name": "Xã Vạn Hưng",
        "codename": "xa_van_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2218,
        "code": 22528,
        "name": "Phường Ninh Hòa",
        "codename": "phuong_ninh_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2219,
        "code": 22546,
        "name": "Xã Bắc Ninh Hòa",
        "codename": "xa_bac_ninh_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2220,
        "code": 22552,
        "name": "Xã Tây Ninh Hòa",
        "codename": "xa_tay_ninh_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2221,
        "code": 22558,
        "name": "Xã Hòa Trí",
        "codename": "xa_hoa_tri",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2222,
        "code": 22561,
        "name": "Phường Đông Ninh Hòa",
        "codename": "phuong_dong_ninh_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2223,
        "code": 22576,
        "name": "Xã Tân Định",
        "codename": "xa_tan_dinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2224,
        "code": 22591,
        "name": "Phường Hòa Thắng",
        "codename": "phuong_hoa_thang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2225,
        "code": 22597,
        "name": "Xã Nam Ninh Hòa",
        "codename": "xa_nam_ninh_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2226,
        "code": 22609,
        "name": "Xã Khánh Vĩnh",
        "codename": "xa_khanh_vinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2227,
        "code": 22612,
        "name": "Xã Trung Khánh Vĩnh",
        "codename": "xa_trung_khanh_vinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2228,
        "code": 22615,
        "name": "Xã Bắc Khánh Vĩnh",
        "codename": "xa_bac_khanh_vinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2229,
        "code": 22624,
        "name": "Xã Tây Khánh Vĩnh",
        "codename": "xa_tay_khanh_vinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2230,
        "code": 22648,
        "name": "Xã Nam Khánh Vĩnh",
        "codename": "xa_nam_khanh_vinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2231,
        "code": 22651,
        "name": "Xã Diên Khánh",
        "codename": "xa_dien_khanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2232,
        "code": 22657,
        "name": "Xã Diên Điền",
        "codename": "xa_dien_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2233,
        "code": 22660,
        "name": "Xã Diên Lâm",
        "codename": "xa_dien_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2234,
        "code": 22672,
        "name": "Xã Diên Thọ",
        "codename": "xa_dien_tho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2235,
        "code": 22678,
        "name": "Xã Diên Lạc",
        "codename": "xa_dien_lac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2236,
        "code": 22702,
        "name": "Xã Suối Hiệp",
        "codename": "xa_suoi_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2237,
        "code": 22708,
        "name": "Xã Suối Dầu",
        "codename": "xa_suoi_dau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2238,
        "code": 22714,
        "name": "Xã Khánh Sơn",
        "codename": "xa_khanh_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2239,
        "code": 22720,
        "name": "Xã Tây Khánh Sơn",
        "codename": "xa_tay_khanh_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2240,
        "code": 22732,
        "name": "Xã Đông Khánh Sơn",
        "codename": "xa_dong_khanh_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2241,
        "code": 22736,
        "name": "Đặc khu Trường Sa",
        "codename": "dac_khu_truong_sa",
        "division_type": "đặc khu",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2242,
        "code": 22738,
        "name": "Phường Đô Vinh",
        "codename": "phuong_do_vinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2243,
        "code": 22741,
        "name": "Phường Bảo An",
        "codename": "phuong_bao_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2244,
        "code": 22759,
        "name": "Phường Phan Rang",
        "codename": "phuong_phan_rang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2245,
        "code": 22780,
        "name": "Phường Đông Hải",
        "codename": "phuong_dong_hai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2246,
        "code": 22786,
        "name": "Xã Bác Ái Tây",
        "codename": "xa_bac_ai_tay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2247,
        "code": 22795,
        "name": "Xã Bác Ái",
        "codename": "xa_bac_ai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2248,
        "code": 22801,
        "name": "Xã Bác Ái Đông",
        "codename": "xa_bac_ai_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2249,
        "code": 22810,
        "name": "Xã Ninh Sơn",
        "codename": "xa_ninh_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2250,
        "code": 22813,
        "name": "Xã Lâm Sơn",
        "codename": "xa_lam_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2251,
        "code": 22822,
        "name": "Xã Mỹ Sơn",
        "codename": "xa_my_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2252,
        "code": 22828,
        "name": "Xã Anh Dũng",
        "codename": "xa_anh_dung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2253,
        "code": 22834,
        "name": "Phường Ninh Chử",
        "codename": "phuong_ninh_chu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2254,
        "code": 22840,
        "name": "Xã Công Hải",
        "codename": "xa_cong_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2255,
        "code": 22846,
        "name": "Xã Vĩnh Hải",
        "codename": "xa_vinh_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2256,
        "code": 22849,
        "name": "Xã Thuận Bắc",
        "codename": "xa_thuan_bac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2257,
        "code": 22852,
        "name": "Xã Ninh Hải",
        "codename": "xa_ninh_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2258,
        "code": 22861,
        "name": "Xã Xuân Hải",
        "codename": "xa_xuan_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2259,
        "code": 22870,
        "name": "Xã Ninh Phước",
        "codename": "xa_ninh_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2260,
        "code": 22873,
        "name": "Xã Phước Hậu",
        "codename": "xa_phuoc_hau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2261,
        "code": 22888,
        "name": "Xã Phước Dinh",
        "codename": "xa_phuoc_dinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2262,
        "code": 22891,
        "name": "Xã Phước Hữu",
        "codename": "xa_phuoc_huu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2263,
        "code": 22897,
        "name": "Xã Thuận Nam",
        "codename": "xa_thuan_nam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2264,
        "code": 22900,
        "name": "Xã Phước Hà",
        "codename": "xa_phuoc_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2265,
        "code": 22909,
        "name": "Xã Cà Ná",
        "codename": "xa_ca_na",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 56
    },
    {
        "id": 2266,
        "code": 66,
        "name": "Tỉnh Đắk Lắk",
        "codename": "dak_lak",
        "division_type": "tỉnh",
        "phone_code": 262,
        "parent_code": null
    },
    {
        "id": 2267,
        "code": 22015,
        "name": "Phường Tuy Hòa",
        "codename": "phuong_tuy_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2268,
        "code": 22045,
        "name": "Phường Bình Kiến",
        "codename": "phuong_binh_kien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2269,
        "code": 22051,
        "name": "Phường Sông Cầu",
        "codename": "phuong_song_cau",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2270,
        "code": 22057,
        "name": "Xã Xuân Lộc",
        "codename": "xa_xuan_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2271,
        "code": 22060,
        "name": "Xã Xuân Cảnh",
        "codename": "xa_xuan_canh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2272,
        "code": 22075,
        "name": "Xã Xuân Thọ",
        "codename": "xa_xuan_tho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2273,
        "code": 22076,
        "name": "Phường Xuân Đài",
        "codename": "phuong_xuan_dai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2274,
        "code": 22081,
        "name": "Xã Đồng Xuân",
        "codename": "xa_dong_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2275,
        "code": 22090,
        "name": "Xã Xuân Lãnh",
        "codename": "xa_xuan_lanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2276,
        "code": 22096,
        "name": "Xã Phú Mỡ",
        "codename": "xa_phu_mo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2277,
        "code": 22111,
        "name": "Xã Xuân Phước",
        "codename": "xa_xuan_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2278,
        "code": 22114,
        "name": "Xã Tuy An Bắc",
        "codename": "xa_tuy_an_bac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2279,
        "code": 22120,
        "name": "Xã Tuy An Đông",
        "codename": "xa_tuy_an_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2280,
        "code": 22132,
        "name": "Xã Tuy An Tây",
        "codename": "xa_tuy_an_tay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2281,
        "code": 22147,
        "name": "Xã Ô Loan",
        "codename": "xa_o_loan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2282,
        "code": 22153,
        "name": "Xã Tuy An Nam",
        "codename": "xa_tuy_an_nam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2283,
        "code": 22165,
        "name": "Xã Sơn Hòa",
        "codename": "xa_son_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2284,
        "code": 22171,
        "name": "Xã Tây Sơn",
        "codename": "xa_tay_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2285,
        "code": 22177,
        "name": "Xã Vân Hòa",
        "codename": "xa_van_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2286,
        "code": 22192,
        "name": "Xã Suối Trai",
        "codename": "xa_suoi_trai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2287,
        "code": 22207,
        "name": "Xã Sông Hinh",
        "codename": "xa_song_hinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2288,
        "code": 22222,
        "name": "Xã Đức Bình",
        "codename": "xa_duc_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2289,
        "code": 22225,
        "name": "Xã Ea Bá",
        "codename": "xa_ea_ba",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2290,
        "code": 22237,
        "name": "Xã Ea Ly",
        "codename": "xa_ea_ly",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2291,
        "code": 22240,
        "name": "Phường Phú Yên",
        "codename": "phuong_phu_yen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2292,
        "code": 22250,
        "name": "Xã Sơn Thành",
        "codename": "xa_son_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2293,
        "code": 22255,
        "name": "Xã Tây Hòa",
        "codename": "xa_tay_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2294,
        "code": 22258,
        "name": "Phường Đông Hòa",
        "codename": "phuong_dong_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2295,
        "code": 22261,
        "name": "Phường Hòa Hiệp",
        "codename": "phuong_hoa_hiep",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2296,
        "code": 22276,
        "name": "Xã Hòa Thịnh",
        "codename": "xa_hoa_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2297,
        "code": 22285,
        "name": "Xã Hòa Mỹ",
        "codename": "xa_hoa_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2298,
        "code": 22291,
        "name": "Xã Hòa Xuân",
        "codename": "xa_hoa_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2299,
        "code": 22303,
        "name": "Xã Phú Hòa 2",
        "codename": "xa_phu_hoa_2",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2300,
        "code": 22319,
        "name": "Xã Phú Hòa 1",
        "codename": "xa_phu_hoa_1",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2301,
        "code": 24121,
        "name": "Phường Tân Lập",
        "codename": "phuong_tan_lap",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2302,
        "code": 24133,
        "name": "Phường Buôn Ma Thuột",
        "codename": "phuong_buon_ma_thuot",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2303,
        "code": 24154,
        "name": "Phường Thành Nhất",
        "codename": "phuong_thanh_nhat",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2304,
        "code": 24163,
        "name": "Phường Tân An",
        "codename": "phuong_tan_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2305,
        "code": 24169,
        "name": "Phường Ea Kao",
        "codename": "phuong_ea_kao",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2306,
        "code": 24175,
        "name": "Xã Hòa Phú",
        "codename": "xa_hoa_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2307,
        "code": 24181,
        "name": "Xã Ea Drăng",
        "codename": "xa_ea_drang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2308,
        "code": 24184,
        "name": "Xã Ea H'Leo",
        "codename": "xa_ea_hleo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2309,
        "code": 24187,
        "name": "Xã Ea Hiao",
        "codename": "xa_ea_hiao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2310,
        "code": 24193,
        "name": "Xã Ea Wy",
        "codename": "xa_ea_wy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2311,
        "code": 24208,
        "name": "Xã Ea Khăl",
        "codename": "xa_ea_khal",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2312,
        "code": 24211,
        "name": "Xã Ea Súp",
        "codename": "xa_ea_sup",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2313,
        "code": 24214,
        "name": "Xã Ia Lốp",
        "codename": "xa_ia_lop",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2314,
        "code": 24217,
        "name": "Xã Ea Rốk",
        "codename": "xa_ea_rok",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2315,
        "code": 24221,
        "name": "Xã Ia Rvê",
        "codename": "xa_ia_rve",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2316,
        "code": 24229,
        "name": "Xã Ea Bung",
        "codename": "xa_ea_bung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2317,
        "code": 24235,
        "name": "Xã Buôn Đôn",
        "codename": "xa_buon_don",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2318,
        "code": 24241,
        "name": "Xã Ea Wer",
        "codename": "xa_ea_wer",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2319,
        "code": 24250,
        "name": "Xã Ea Nuôl",
        "codename": "xa_ea_nuol",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2320,
        "code": 24259,
        "name": "Xã Quảng Phú",
        "codename": "xa_quang_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2321,
        "code": 24265,
        "name": "Xã Ea Kiết",
        "codename": "xa_ea_kiet",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2322,
        "code": 24277,
        "name": "Xã Ea Tul",
        "codename": "xa_ea_tul",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2323,
        "code": 24280,
        "name": "Xã Cư M'gar",
        "codename": "xa_cu_mgar",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2324,
        "code": 24286,
        "name": "Xã Ea M'Droh",
        "codename": "xa_ea_mdroh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2325,
        "code": 24301,
        "name": "Xã Cuôr Đăng",
        "codename": "xa_cuor_dang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2326,
        "code": 24305,
        "name": "Phường Buôn Hồ",
        "codename": "phuong_buon_ho",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2327,
        "code": 24310,
        "name": "Xã Krông Búk",
        "codename": "xa_krong_buk",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2328,
        "code": 24313,
        "name": "Xã Cư Pơng",
        "codename": "xa_cu_pong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2329,
        "code": 24316,
        "name": "Xã Pơng Drang",
        "codename": "xa_pong_drang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2330,
        "code": 24328,
        "name": "Xã Ea Drông",
        "codename": "xa_ea_drong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2331,
        "code": 24340,
        "name": "Phường Cư Bao",
        "codename": "phuong_cu_bao",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2332,
        "code": 24343,
        "name": "Xã Krông Năng",
        "codename": "xa_krong_nang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2333,
        "code": 24346,
        "name": "Xã Dliê Ya",
        "codename": "xa_dlie_ya",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2334,
        "code": 24352,
        "name": "Xã Tam Giang",
        "codename": "xa_tam_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2335,
        "code": 24364,
        "name": "Xã Phú Xuân",
        "codename": "xa_phu_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2336,
        "code": 24373,
        "name": "Xã Ea Kar",
        "codename": "xa_ea_kar",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2337,
        "code": 24376,
        "name": "Xã Ea Knốp",
        "codename": "xa_ea_knop",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2338,
        "code": 24400,
        "name": "Xã Ea Păl",
        "codename": "xa_ea_pal",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2339,
        "code": 24403,
        "name": "Xã Ea Ô",
        "codename": "xa_ea_o",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2340,
        "code": 24406,
        "name": "Xã Cư Yang",
        "codename": "xa_cu_yang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2341,
        "code": 24412,
        "name": "Xã M'Drắk",
        "codename": "xa_mdrak",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2342,
        "code": 24415,
        "name": "Xã Cư Prao",
        "codename": "xa_cu_prao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2343,
        "code": 24433,
        "name": "Xã Ea Riêng",
        "codename": "xa_ea_rieng",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2344,
        "code": 24436,
        "name": "Xã Cư M'ta",
        "codename": "xa_cu_mta",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2345,
        "code": 24444,
        "name": "Xã Krông Á",
        "codename": "xa_krong_a",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2346,
        "code": 24445,
        "name": "Xã Ea Trang",
        "codename": "xa_ea_trang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2347,
        "code": 24448,
        "name": "Xã Krông Bông",
        "codename": "xa_krong_bong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2348,
        "code": 24454,
        "name": "Xã Dang Kang",
        "codename": "xa_dang_kang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2349,
        "code": 24472,
        "name": "Xã Hòa Sơn",
        "codename": "xa_hoa_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2350,
        "code": 24478,
        "name": "Xã Cư Pui",
        "codename": "xa_cu_pui",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2351,
        "code": 24484,
        "name": "Xã Yang Mao",
        "codename": "xa_yang_mao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2352,
        "code": 24490,
        "name": "Xã Krông Pắc",
        "codename": "xa_krong_pac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2353,
        "code": 24496,
        "name": "Xã Ea Kly",
        "codename": "xa_ea_kly",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2354,
        "code": 24502,
        "name": "Xã Ea Phê",
        "codename": "xa_ea_phe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2355,
        "code": 24505,
        "name": "Xã Ea Knuếc",
        "codename": "xa_ea_knuec",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2356,
        "code": 24526,
        "name": "Xã Tân Tiến",
        "codename": "xa_tan_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2357,
        "code": 24529,
        "name": "Xã Vụ Bổn",
        "codename": "xa_vu_bon",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2358,
        "code": 24538,
        "name": "Xã Krông Ana",
        "codename": "xa_krong_ana",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2359,
        "code": 24540,
        "name": "Xã Ea Ning",
        "codename": "xa_ea_ning",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2360,
        "code": 24544,
        "name": "Xã Ea Ktur",
        "codename": "xa_ea_ktur",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2361,
        "code": 24559,
        "name": "Xã Ea Na",
        "codename": "xa_ea_na",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2362,
        "code": 24561,
        "name": "Xã Dray Bhăng",
        "codename": "xa_dray_bhang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2363,
        "code": 24568,
        "name": "Xã Dur Kmăl",
        "codename": "xa_dur_kmal",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2364,
        "code": 24580,
        "name": "Xã Liên Sơn Lắk",
        "codename": "xa_lien_son_lak",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2365,
        "code": 24595,
        "name": "Xã Đắk Liêng",
        "codename": "xa_dak_lieng",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2366,
        "code": 24598,
        "name": "Xã Đắk Phơi",
        "codename": "xa_dak_phoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2367,
        "code": 24604,
        "name": "Xã Krông Nô",
        "codename": "xa_krong_no",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2368,
        "code": 24607,
        "name": "Xã Nam Ka",
        "codename": "xa_nam_ka",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 66
    },
    {
        "id": 2369,
        "code": 68,
        "name": "Tỉnh Lâm Đồng",
        "codename": "lam_dong",
        "division_type": "tỉnh",
        "phone_code": 263,
        "parent_code": null
    },
    {
        "id": 2370,
        "code": 22918,
        "name": "Phường Mũi Né",
        "codename": "phuong_mui_ne",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2371,
        "code": 22924,
        "name": "Phường Phú Thuỷ",
        "codename": "phuong_phu_thuy",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2372,
        "code": 22933,
        "name": "Phường Hàm Thắng",
        "codename": "phuong_ham_thang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2373,
        "code": 22945,
        "name": "Phường Phan Thiết",
        "codename": "phuong_phan_thiet",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2374,
        "code": 22954,
        "name": "Phường Tiến Thành",
        "codename": "phuong_tien_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2375,
        "code": 22960,
        "name": "Phường Bình Thuận",
        "codename": "phuong_binh_thuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2376,
        "code": 22963,
        "name": "Xã Tuyên Quang",
        "codename": "xa_tuyen_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2377,
        "code": 22969,
        "name": "Xã Liên Hương",
        "codename": "xa_lien_huong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2378,
        "code": 22972,
        "name": "Xã Phan Rí Cửa",
        "codename": "xa_phan_ri_cua",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2379,
        "code": 22978,
        "name": "Xã Tuy Phong",
        "codename": "xa_tuy_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2380,
        "code": 22981,
        "name": "Xã Vĩnh Hảo",
        "codename": "xa_vinh_hao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2381,
        "code": 23005,
        "name": "Xã Bắc Bình",
        "codename": "xa_bac_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2382,
        "code": 23008,
        "name": "Xã Phan Sơn",
        "codename": "xa_phan_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2383,
        "code": 23020,
        "name": "Xã Hải Ninh",
        "codename": "xa_hai_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2384,
        "code": 23023,
        "name": "Xã Sông Lũy",
        "codename": "xa_song_luy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2385,
        "code": 23032,
        "name": "Xã Lương Sơn",
        "codename": "xa_luong_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2386,
        "code": 23041,
        "name": "Xã Hồng Thái",
        "codename": "xa_hong_thai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2387,
        "code": 23053,
        "name": "Xã Hòa Thắng",
        "codename": "xa_hoa_thang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2388,
        "code": 23059,
        "name": "Xã Hàm Thuận",
        "codename": "xa_ham_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2389,
        "code": 23065,
        "name": "Xã La Dạ",
        "codename": "xa_la_da",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2390,
        "code": 23074,
        "name": "Xã Đông Giang",
        "codename": "xa_dong_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2391,
        "code": 23086,
        "name": "Xã Hồng Sơn",
        "codename": "xa_hong_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2392,
        "code": 23089,
        "name": "Xã Hàm Thuận Bắc",
        "codename": "xa_ham_thuan_bac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2393,
        "code": 23095,
        "name": "Xã Hàm Liêm",
        "codename": "xa_ham_liem",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2394,
        "code": 23110,
        "name": "Xã Hàm Thuận Nam",
        "codename": "xa_ham_thuan_nam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2395,
        "code": 23122,
        "name": "Xã Hàm Thạnh",
        "codename": "xa_ham_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2396,
        "code": 23128,
        "name": "Xã Hàm Kiệm",
        "codename": "xa_ham_kiem",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2397,
        "code": 23134,
        "name": "Xã Tân Lập",
        "codename": "xa_tan_lap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2398,
        "code": 23143,
        "name": "Xã Tân Thành",
        "codename": "xa_tan_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2399,
        "code": 23149,
        "name": "Xã Tánh Linh",
        "codename": "xa_tanh_linh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2400,
        "code": 23152,
        "name": "Xã Bắc Ruộng",
        "codename": "xa_bac_ruong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2401,
        "code": 23158,
        "name": "Xã Nghị Đức",
        "codename": "xa_nghi_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2402,
        "code": 23173,
        "name": "Xã Đồng Kho",
        "codename": "xa_dong_kho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2403,
        "code": 23188,
        "name": "Xã Suối Kiết",
        "codename": "xa_suoi_kiet",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2404,
        "code": 23191,
        "name": "Xã Đức Linh",
        "codename": "xa_duc_linh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2405,
        "code": 23194,
        "name": "Xã Hoài Đức",
        "codename": "xa_hoai_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2406,
        "code": 23200,
        "name": "Xã Nam Thành",
        "codename": "xa_nam_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2407,
        "code": 23227,
        "name": "Xã Trà Tân",
        "codename": "xa_tra_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2408,
        "code": 23230,
        "name": "Xã Tân Minh",
        "codename": "xa_tan_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2409,
        "code": 23231,
        "name": "Phường Phước Hội",
        "codename": "phuong_phuoc_hoi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2410,
        "code": 23235,
        "name": "Phường La Gi",
        "codename": "phuong_la_gi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2411,
        "code": 23236,
        "name": "Xã Hàm Tân",
        "codename": "xa_ham_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2412,
        "code": 23246,
        "name": "Xã Tân Hải",
        "codename": "xa_tan_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2413,
        "code": 23266,
        "name": "Xã Sơn Mỹ",
        "codename": "xa_son_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2414,
        "code": 23272,
        "name": "Đặc khu Phú Quý",
        "codename": "dac_khu_phu_quy",
        "division_type": "đặc khu",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2415,
        "code": 24611,
        "name": "Phường Bắc Gia Nghĩa",
        "codename": "phuong_bac_gia_nghia",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2416,
        "code": 24615,
        "name": "Phường Nam Gia Nghĩa",
        "codename": "phuong_nam_gia_nghia",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2417,
        "code": 24616,
        "name": "Xã Quảng Sơn",
        "codename": "xa_quang_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2418,
        "code": 24617,
        "name": "Phường Đông Gia Nghĩa",
        "codename": "phuong_dong_gia_nghia",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2419,
        "code": 24620,
        "name": "Xã Quảng Hòa",
        "codename": "xa_quang_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2420,
        "code": 24631,
        "name": "Xã Quảng Khê",
        "codename": "xa_quang_khe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2421,
        "code": 24637,
        "name": "Xã Tà Đùng",
        "codename": "xa_ta_dung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2422,
        "code": 24640,
        "name": "Xã Cư Jút",
        "codename": "xa_cu_jut",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2423,
        "code": 24646,
        "name": "Xã Đắk Wil",
        "codename": "xa_dak_wil",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2424,
        "code": 24649,
        "name": "Xã Nam Dong",
        "codename": "xa_nam_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2425,
        "code": 24664,
        "name": "Xã Đức Lập",
        "codename": "xa_duc_lap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2426,
        "code": 24670,
        "name": "Xã Đắk Mil",
        "codename": "xa_dak_mil",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2427,
        "code": 24678,
        "name": "Xã Đắk Sắk",
        "codename": "xa_dak_sak",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2428,
        "code": 24682,
        "name": "Xã Thuận An",
        "codename": "xa_thuan_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2429,
        "code": 24688,
        "name": "Xã Krông Nô",
        "codename": "xa_krong_no",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2430,
        "code": 24697,
        "name": "Xã Nam Đà",
        "codename": "xa_nam_da",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2431,
        "code": 24703,
        "name": "Xã Nâm Nung",
        "codename": "xa_nam_nung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2432,
        "code": 24712,
        "name": "Xã Quảng Phú",
        "codename": "xa_quang_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2433,
        "code": 24717,
        "name": "Xã Đức An",
        "codename": "xa_duc_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2434,
        "code": 24718,
        "name": "Xã Đắk Song",
        "codename": "xa_dak_song",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2435,
        "code": 24722,
        "name": "Xã Thuận Hạnh",
        "codename": "xa_thuan_hanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2436,
        "code": 24730,
        "name": "Xã Trường Xuân",
        "codename": "xa_truong_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2437,
        "code": 24733,
        "name": "Xã Kiến Đức",
        "codename": "xa_kien_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2438,
        "code": 24736,
        "name": "Xã Quảng Trực",
        "codename": "xa_quang_truc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2439,
        "code": 24739,
        "name": "Xã Tuy Đức",
        "codename": "xa_tuy_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2440,
        "code": 24748,
        "name": "Xã Quảng Tân",
        "codename": "xa_quang_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2441,
        "code": 24751,
        "name": "Xã Nhân Cơ",
        "codename": "xa_nhan_co",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2442,
        "code": 24760,
        "name": "Xã Quảng Tín",
        "codename": "xa_quang_tin",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2443,
        "code": 24778,
        "name": "Phường Lâm Viên - Đà Lạt",
        "codename": "phuong_lam_vien_da_lat",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2444,
        "code": 24781,
        "name": "Phường Xuân Hương - Đà Lạt",
        "codename": "phuong_xuan_huong_da_lat",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2445,
        "code": 24787,
        "name": "Phường Cam Ly - Đà Lạt",
        "codename": "phuong_cam_ly_da_lat",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2446,
        "code": 24805,
        "name": "Phường Xuân Trường - Đà Lạt",
        "codename": "phuong_xuan_truong_da_lat",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2447,
        "code": 24820,
        "name": "Phường 2 Bảo Lộc",
        "codename": "phuong_2_bao_loc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2448,
        "code": 24823,
        "name": "Phường 1 Bảo Lộc",
        "codename": "phuong_1_bao_loc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2449,
        "code": 24829,
        "name": "Phường B'Lao",
        "codename": "phuong_blao",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2450,
        "code": 24841,
        "name": "Phường 3 Bảo Lộc",
        "codename": "phuong_3_bao_loc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2451,
        "code": 24846,
        "name": "Phường Lang Biang - Đà Lạt",
        "codename": "phuong_lang_biang_da_lat",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2452,
        "code": 24848,
        "name": "Xã Lạc Dương",
        "codename": "xa_lac_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2453,
        "code": 24853,
        "name": "Xã Đam Rông 4",
        "codename": "xa_dam_rong_4",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2454,
        "code": 24868,
        "name": "Xã Nam Ban Lâm Hà",
        "codename": "xa_nam_ban_lam_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2455,
        "code": 24871,
        "name": "Xã Đinh Văn Lâm Hà",
        "codename": "xa_dinh_van_lam_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2456,
        "code": 24875,
        "name": "Xã Đam Rông 3",
        "codename": "xa_dam_rong_3",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2457,
        "code": 24877,
        "name": "Xã Đam Rông 2",
        "codename": "xa_dam_rong_2",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2458,
        "code": 24883,
        "name": "Xã Nam Hà Lâm Hà",
        "codename": "xa_nam_ha_lam_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2459,
        "code": 24886,
        "name": "Xã Đam Rông 1",
        "codename": "xa_dam_rong_1",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2460,
        "code": 24895,
        "name": "Xã Phú Sơn Lâm Hà",
        "codename": "xa_phu_son_lam_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2461,
        "code": 24907,
        "name": "Xã Phúc Thọ Lâm Hà",
        "codename": "xa_phuc_tho_lam_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2462,
        "code": 24916,
        "name": "Xã Tân Hà Lâm Hà",
        "codename": "xa_tan_ha_lam_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2463,
        "code": 24931,
        "name": "Xã Đơn Dương",
        "codename": "xa_don_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2464,
        "code": 24934,
        "name": "Xã D'Ran",
        "codename": "xa_dran",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2465,
        "code": 24943,
        "name": "Xã Ka Đô",
        "codename": "xa_ka_do",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2466,
        "code": 24955,
        "name": "Xã Quảng Lập",
        "codename": "xa_quang_lap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2467,
        "code": 24958,
        "name": "Xã Đức Trọng",
        "codename": "xa_duc_trong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2468,
        "code": 24967,
        "name": "Xã Hiệp Thạnh",
        "codename": "xa_hiep_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2469,
        "code": 24976,
        "name": "Xã Tân Hội",
        "codename": "xa_tan_hoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2470,
        "code": 24985,
        "name": "Xã Ninh Gia",
        "codename": "xa_ninh_gia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2471,
        "code": 24988,
        "name": "Xã Tà Năng",
        "codename": "xa_ta_nang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2472,
        "code": 24991,
        "name": "Xã Tà Hine",
        "codename": "xa_ta_hine",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2473,
        "code": 25000,
        "name": "Xã Di Linh",
        "codename": "xa_di_linh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2474,
        "code": 25007,
        "name": "Xã Đinh Trang Thượng",
        "codename": "xa_dinh_trang_thuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2475,
        "code": 25015,
        "name": "Xã Gia Hiệp",
        "codename": "xa_gia_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2476,
        "code": 25018,
        "name": "Xã Bảo Thuận",
        "codename": "xa_bao_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2477,
        "code": 25036,
        "name": "Xã Hòa Ninh",
        "codename": "xa_hoa_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2478,
        "code": 25042,
        "name": "Xã Hòa Bắc",
        "codename": "xa_hoa_bac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2479,
        "code": 25051,
        "name": "Xã Sơn Điền",
        "codename": "xa_son_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2480,
        "code": 25054,
        "name": "Xã Bảo Lâm 1",
        "codename": "xa_bao_lam_1",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2481,
        "code": 25057,
        "name": "Xã Bảo Lâm 5",
        "codename": "xa_bao_lam_5",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2482,
        "code": 25063,
        "name": "Xã Bảo Lâm 4",
        "codename": "xa_bao_lam_4",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2483,
        "code": 25084,
        "name": "Xã Bảo Lâm 2",
        "codename": "xa_bao_lam_2",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2484,
        "code": 25093,
        "name": "Xã Bảo Lâm 3",
        "codename": "xa_bao_lam_3",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2485,
        "code": 25099,
        "name": "Xã Đạ Huoai",
        "codename": "xa_da_huoai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2486,
        "code": 25105,
        "name": "Xã Đạ Huoai 2",
        "codename": "xa_da_huoai_2",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2487,
        "code": 25114,
        "name": "Xã Đạ Huoai 3",
        "codename": "xa_da_huoai_3",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2488,
        "code": 25126,
        "name": "Xã Đạ Tẻh",
        "codename": "xa_da_teh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2489,
        "code": 25135,
        "name": "Xã Đạ Tẻh 3",
        "codename": "xa_da_teh_3",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2490,
        "code": 25138,
        "name": "Xã Đạ Tẻh 2",
        "codename": "xa_da_teh_2",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2491,
        "code": 25159,
        "name": "Xã Cát Tiên",
        "codename": "xa_cat_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2492,
        "code": 25162,
        "name": "Xã Cát Tiên 3",
        "codename": "xa_cat_tien_3",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2493,
        "code": 25180,
        "name": "Xã Cát Tiên 2",
        "codename": "xa_cat_tien_2",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 68
    },
    {
        "id": 2494,
        "code": 75,
        "name": "Tỉnh Đồng Nai",
        "codename": "dong_nai",
        "division_type": "tỉnh",
        "phone_code": 251,
        "parent_code": null
    },
    {
        "id": 2495,
        "code": 25195,
        "name": "Phường Bình Phước",
        "codename": "phuong_binh_phuoc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2496,
        "code": 25210,
        "name": "Phường Đồng Xoài",
        "codename": "phuong_dong_xoai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2497,
        "code": 25217,
        "name": "Phường Phước Long",
        "codename": "phuong_phuoc_long",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2498,
        "code": 25220,
        "name": "Phường Phước Bình",
        "codename": "phuong_phuoc_binh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2499,
        "code": 25222,
        "name": "Xã Bù Gia Mập",
        "codename": "xa_bu_gia_map",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2500,
        "code": 25225,
        "name": "Xã Đăk Ơ",
        "codename": "xa_dak_o",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2501,
        "code": 25231,
        "name": "Xã Đa Kia",
        "codename": "xa_da_kia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2502,
        "code": 25246,
        "name": "Xã Bình Tân",
        "codename": "xa_binh_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2503,
        "code": 25252,
        "name": "Xã Phú Riềng",
        "codename": "xa_phu_rieng",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2504,
        "code": 25255,
        "name": "Xã Long Hà",
        "codename": "xa_long_ha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2505,
        "code": 25261,
        "name": "Xã Phú Trung",
        "codename": "xa_phu_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2506,
        "code": 25267,
        "name": "Xã Phú Nghĩa",
        "codename": "xa_phu_nghia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2507,
        "code": 25270,
        "name": "Xã Lộc Ninh",
        "codename": "xa_loc_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2508,
        "code": 25279,
        "name": "Xã Lộc Tấn",
        "codename": "xa_loc_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2509,
        "code": 25280,
        "name": "Xã Lộc Thạnh",
        "codename": "xa_loc_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2510,
        "code": 25292,
        "name": "Xã Lộc Quang",
        "codename": "xa_loc_quang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2511,
        "code": 25294,
        "name": "Xã Lộc Thành",
        "codename": "xa_loc_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2512,
        "code": 25303,
        "name": "Xã Lộc Hưng",
        "codename": "xa_loc_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2513,
        "code": 25308,
        "name": "Xã Thiện Hưng",
        "codename": "xa_thien_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2514,
        "code": 25309,
        "name": "Xã Hưng Phước",
        "codename": "xa_hung_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2515,
        "code": 25318,
        "name": "Xã Tân Tiến",
        "codename": "xa_tan_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2516,
        "code": 25326,
        "name": "Phường Bình Long",
        "codename": "phuong_binh_long",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2517,
        "code": 25333,
        "name": "Phường An Lộc",
        "codename": "phuong_an_loc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2518,
        "code": 25345,
        "name": "Xã Tân Hưng",
        "codename": "xa_tan_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2519,
        "code": 25349,
        "name": "Xã Minh Đức",
        "codename": "xa_minh_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2520,
        "code": 25351,
        "name": "Xã Tân Quan",
        "codename": "xa_tan_quan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2521,
        "code": 25357,
        "name": "Xã Tân Khai",
        "codename": "xa_tan_khai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2522,
        "code": 25363,
        "name": "Xã Đồng Phú",
        "codename": "xa_dong_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2523,
        "code": 25378,
        "name": "Xã Tân Lợi",
        "codename": "xa_tan_loi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2524,
        "code": 25387,
        "name": "Xã Thuận Lợi",
        "codename": "xa_thuan_loi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2525,
        "code": 25390,
        "name": "Xã Đồng Tâm",
        "codename": "xa_dong_tam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2526,
        "code": 25396,
        "name": "Xã Bù Đăng",
        "codename": "xa_bu_dang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2527,
        "code": 25399,
        "name": "Xã Đak Nhau",
        "codename": "xa_dak_nhau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2528,
        "code": 25402,
        "name": "Xã Thọ Sơn",
        "codename": "xa_tho_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2529,
        "code": 25405,
        "name": "Xã Bom Bo",
        "codename": "xa_bom_bo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2530,
        "code": 25417,
        "name": "Xã Nghĩa Trung",
        "codename": "xa_nghia_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2531,
        "code": 25420,
        "name": "Xã Phước Sơn",
        "codename": "xa_phuoc_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2532,
        "code": 25432,
        "name": "Phường Chơn Thành",
        "codename": "phuong_chon_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2533,
        "code": 25441,
        "name": "Phường Minh Hưng",
        "codename": "phuong_minh_hung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2534,
        "code": 25450,
        "name": "Xã Nha Bích",
        "codename": "xa_nha_bich",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2535,
        "code": 25993,
        "name": "Phường Trảng Dài",
        "codename": "phuong_trang_dai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2536,
        "code": 26005,
        "name": "Phường Hố Nai",
        "codename": "phuong_ho_nai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2537,
        "code": 26017,
        "name": "Phường Tam Hiệp",
        "codename": "phuong_tam_hiep",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2538,
        "code": 26020,
        "name": "Phường Long Bình",
        "codename": "phuong_long_binh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2539,
        "code": 26041,
        "name": "Phường Trấn Biên",
        "codename": "phuong_tran_bien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2540,
        "code": 26068,
        "name": "Phường Biên Hòa",
        "codename": "phuong_bien_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2541,
        "code": 26080,
        "name": "Phường Long Khánh",
        "codename": "phuong_long_khanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2542,
        "code": 26089,
        "name": "Phường Bình Lộc",
        "codename": "phuong_binh_loc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2543,
        "code": 26098,
        "name": "Phường Bảo Vinh",
        "codename": "phuong_bao_vinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2544,
        "code": 26104,
        "name": "Phường Xuân Lập",
        "codename": "phuong_xuan_lap",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2545,
        "code": 26113,
        "name": "Phường Hàng Gòn",
        "codename": "phuong_hang_gon",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2546,
        "code": 26116,
        "name": "Xã Tân Phú",
        "codename": "xa_tan_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2547,
        "code": 26119,
        "name": "Xã Đak Lua",
        "codename": "xa_dak_lua",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2548,
        "code": 26122,
        "name": "Xã Nam Cát Tiên",
        "codename": "xa_nam_cat_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2549,
        "code": 26134,
        "name": "Xã Tà Lài",
        "codename": "xa_ta_lai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2550,
        "code": 26158,
        "name": "Xã Phú Lâm",
        "codename": "xa_phu_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2551,
        "code": 26170,
        "name": "Xã Trị An",
        "codename": "xa_tri_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2552,
        "code": 26173,
        "name": "Xã Phú Lý",
        "codename": "xa_phu_ly",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2553,
        "code": 26179,
        "name": "Xã Tân An",
        "codename": "xa_tan_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2554,
        "code": 26188,
        "name": "Phường Tân Triều",
        "codename": "phuong_tan_trieu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2555,
        "code": 26206,
        "name": "Xã Định Quán",
        "codename": "xa_dinh_quan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2556,
        "code": 26209,
        "name": "Xã Thanh Sơn",
        "codename": "xa_thanh_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2557,
        "code": 26215,
        "name": "Xã Phú Vinh",
        "codename": "xa_phu_vinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2558,
        "code": 26221,
        "name": "Xã Phú Hòa",
        "codename": "xa_phu_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2559,
        "code": 26227,
        "name": "Xã La Ngà",
        "codename": "xa_la_nga",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2560,
        "code": 26248,
        "name": "Xã Trảng Bom",
        "codename": "xa_trang_bom",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2561,
        "code": 26254,
        "name": "Xã Bàu Hàm",
        "codename": "xa_bau_ham",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2562,
        "code": 26278,
        "name": "Xã Bình Minh",
        "codename": "xa_binh_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2563,
        "code": 26281,
        "name": "Xã Hưng Thịnh",
        "codename": "xa_hung_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2564,
        "code": 26296,
        "name": "Xã An Viễn",
        "codename": "xa_an_vien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2565,
        "code": 26299,
        "name": "Xã Thống Nhất",
        "codename": "xa_thong_nhat",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2566,
        "code": 26311,
        "name": "Xã Gia Kiệm",
        "codename": "xa_gia_kiem",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2567,
        "code": 26326,
        "name": "Xã Dầu Giây",
        "codename": "xa_dau_giay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2568,
        "code": 26332,
        "name": "Xã Xuân Quế",
        "codename": "xa_xuan_que",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2569,
        "code": 26341,
        "name": "Xã Cẩm Mỹ",
        "codename": "xa_cam_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2570,
        "code": 26347,
        "name": "Xã Xuân Đường",
        "codename": "xa_xuan_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2571,
        "code": 26359,
        "name": "Xã Xuân Đông",
        "codename": "xa_xuan_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2572,
        "code": 26362,
        "name": "Xã Sông Ray",
        "codename": "xa_song_ray",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2573,
        "code": 26368,
        "name": "Xã Long Thành",
        "codename": "xa_long_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2574,
        "code": 26374,
        "name": "Phường Tam Phước",
        "codename": "phuong_tam_phuoc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2575,
        "code": 26377,
        "name": "Phường Phước Tân",
        "codename": "phuong_phuoc_tan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2576,
        "code": 26380,
        "name": "Phường Long Hưng",
        "codename": "phuong_long_hung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2577,
        "code": 26383,
        "name": "Xã An Phước",
        "codename": "xa_an_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2578,
        "code": 26389,
        "name": "Xã Bình An",
        "codename": "xa_binh_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2579,
        "code": 26413,
        "name": "Xã Long Phước",
        "codename": "xa_long_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2580,
        "code": 26422,
        "name": "Xã Phước Thái",
        "codename": "xa_phuoc_thai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2581,
        "code": 26425,
        "name": "Xã Xuân Lộc",
        "codename": "xa_xuan_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2582,
        "code": 26428,
        "name": "Xã Xuân Bắc",
        "codename": "xa_xuan_bac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2583,
        "code": 26434,
        "name": "Xã Xuân Thành",
        "codename": "xa_xuan_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2584,
        "code": 26446,
        "name": "Xã Xuân Hòa",
        "codename": "xa_xuan_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2585,
        "code": 26458,
        "name": "Xã Xuân Phú",
        "codename": "xa_xuan_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2586,
        "code": 26461,
        "name": "Xã Xuân Định",
        "codename": "xa_xuan_dinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2587,
        "code": 26485,
        "name": "Xã Nhơn Trạch",
        "codename": "xa_nhon_trach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2588,
        "code": 26491,
        "name": "Xã Đại Phước",
        "codename": "xa_dai_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2589,
        "code": 26503,
        "name": "Xã Phước An",
        "codename": "xa_phuoc_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 75
    },
    {
        "id": 2590,
        "code": 79,
        "name": "Thành phố Hồ Chí Minh",
        "codename": "ho_chi_minh",
        "division_type": "thành phố trung ương",
        "phone_code": 28,
        "parent_code": null
    },
    {
        "id": 2591,
        "code": 25747,
        "name": "Phường Thủ Dầu Một",
        "codename": "phuong_thu_dau_mot",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2592,
        "code": 25750,
        "name": "Phường Phú Lợi",
        "codename": "phuong_phu_loi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2593,
        "code": 25760,
        "name": "Phường Bình Dương",
        "codename": "phuong_binh_duong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2594,
        "code": 25768,
        "name": "Phường Phú An",
        "codename": "phuong_phu_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2595,
        "code": 25771,
        "name": "Phường Chánh Hiệp",
        "codename": "phuong_chanh_hiep",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2596,
        "code": 25777,
        "name": "Xã Dầu Tiếng",
        "codename": "xa_dau_tieng",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2597,
        "code": 25780,
        "name": "Xã Minh Thạnh",
        "codename": "xa_minh_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2598,
        "code": 25792,
        "name": "Xã Long Hòa",
        "codename": "xa_long_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2599,
        "code": 25807,
        "name": "Xã Thanh An",
        "codename": "xa_thanh_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2600,
        "code": 25813,
        "name": "Phường Bến Cát",
        "codename": "phuong_ben_cat",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2601,
        "code": 25819,
        "name": "Xã Trừ Văn Thố",
        "codename": "xa_tru_van_tho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2602,
        "code": 25822,
        "name": "Xã Bàu Bàng",
        "codename": "xa_bau_bang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2603,
        "code": 25837,
        "name": "Phường Chánh Phú Hòa",
        "codename": "phuong_chanh_phu_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2604,
        "code": 25840,
        "name": "Phường Long Nguyên",
        "codename": "phuong_long_nguyen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2605,
        "code": 25843,
        "name": "Phường Tây Nam",
        "codename": "phuong_tay_nam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2606,
        "code": 25846,
        "name": "Phường Thới Hòa",
        "codename": "phuong_thoi_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2607,
        "code": 25849,
        "name": "Phường Hòa Lợi",
        "codename": "phuong_hoa_loi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2608,
        "code": 25858,
        "name": "Xã Phú Giáo",
        "codename": "xa_phu_giao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2609,
        "code": 25864,
        "name": "Xã Phước Thành",
        "codename": "xa_phuoc_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2610,
        "code": 25867,
        "name": "Xã An Long",
        "codename": "xa_an_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2611,
        "code": 25882,
        "name": "Xã Phước Hòa",
        "codename": "xa_phuoc_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2612,
        "code": 25888,
        "name": "Phường Tân Uyên",
        "codename": "phuong_tan_uyen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2613,
        "code": 25891,
        "name": "Phường Tân Khánh",
        "codename": "phuong_tan_khanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2614,
        "code": 25906,
        "name": "Xã Bắc Tân Uyên",
        "codename": "xa_bac_tan_uyen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2615,
        "code": 25909,
        "name": "Xã Thường Tân",
        "codename": "xa_thuong_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2616,
        "code": 25912,
        "name": "Phường Vĩnh Tân",
        "codename": "phuong_vinh_tan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2617,
        "code": 25915,
        "name": "Phường Bình Cơ",
        "codename": "phuong_binh_co",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2618,
        "code": 25920,
        "name": "Phường Tân Hiệp",
        "codename": "phuong_tan_hiep",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2619,
        "code": 25942,
        "name": "Phường Dĩ An",
        "codename": "phuong_di_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2620,
        "code": 25945,
        "name": "Phường Tân Đông Hiệp",
        "codename": "phuong_tan_dong_hiep",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2621,
        "code": 25951,
        "name": "Phường Đông Hòa",
        "codename": "phuong_dong_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2622,
        "code": 25966,
        "name": "Phường Lái Thiêu",
        "codename": "phuong_lai_thieu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2623,
        "code": 25969,
        "name": "Phường Thuận Giao",
        "codename": "phuong_thuan_giao",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2624,
        "code": 25975,
        "name": "Phường An Phú",
        "codename": "phuong_an_phu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2625,
        "code": 25978,
        "name": "Phường Thuận An",
        "codename": "phuong_thuan_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2626,
        "code": 25987,
        "name": "Phường Bình Hòa",
        "codename": "phuong_binh_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2627,
        "code": 26506,
        "name": "Phường Vũng Tàu",
        "codename": "phuong_vung_tau",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2628,
        "code": 26526,
        "name": "Phường Tam Thắng",
        "codename": "phuong_tam_thang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2629,
        "code": 26536,
        "name": "Phường Rạch Dừa",
        "codename": "phuong_rach_dua",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2630,
        "code": 26542,
        "name": "Phường Phước Thắng",
        "codename": "phuong_phuoc_thang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2631,
        "code": 26545,
        "name": "Xã Long Sơn",
        "codename": "xa_long_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2632,
        "code": 26560,
        "name": "Phường Bà Rịa",
        "codename": "phuong_ba_ria",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2633,
        "code": 26566,
        "name": "Phường Long Hương",
        "codename": "phuong_long_huong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2634,
        "code": 26572,
        "name": "Phường Tam Long",
        "codename": "phuong_tam_long",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2635,
        "code": 26575,
        "name": "Xã Ngãi Giao",
        "codename": "xa_ngai_giao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2636,
        "code": 26584,
        "name": "Xã Xuân Sơn",
        "codename": "xa_xuan_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2637,
        "code": 26590,
        "name": "Xã Bình Giã",
        "codename": "xa_binh_gia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2638,
        "code": 26596,
        "name": "Xã Châu Đức",
        "codename": "xa_chau_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2639,
        "code": 26608,
        "name": "Xã Kim Long",
        "codename": "xa_kim_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2640,
        "code": 26617,
        "name": "Xã Nghĩa Thành",
        "codename": "xa_nghia_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2641,
        "code": 26620,
        "name": "Xã Hồ Tràm",
        "codename": "xa_ho_tram",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2642,
        "code": 26632,
        "name": "Xã Xuyên Mộc",
        "codename": "xa_xuyen_moc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2643,
        "code": 26638,
        "name": "Xã Bàu Lâm",
        "codename": "xa_bau_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2644,
        "code": 26641,
        "name": "Xã Hòa Hội",
        "codename": "xa_hoa_hoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2645,
        "code": 26647,
        "name": "Xã Hòa Hiệp",
        "codename": "xa_hoa_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2646,
        "code": 26656,
        "name": "Xã Bình Châu",
        "codename": "xa_binh_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2647,
        "code": 26659,
        "name": "Xã Long Điền",
        "codename": "xa_long_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2648,
        "code": 26662,
        "name": "Xã Long Hải",
        "codename": "xa_long_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2649,
        "code": 26680,
        "name": "Xã Đất Đỏ",
        "codename": "xa_dat_do",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2650,
        "code": 26686,
        "name": "Xã Phước Hải",
        "codename": "xa_phuoc_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2651,
        "code": 26704,
        "name": "Phường Phú Mỹ",
        "codename": "phuong_phu_my",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2652,
        "code": 26710,
        "name": "Phường Tân Hải",
        "codename": "phuong_tan_hai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2653,
        "code": 26713,
        "name": "Phường Tân Phước",
        "codename": "phuong_tan_phuoc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2654,
        "code": 26725,
        "name": "Phường Tân Thành",
        "codename": "phuong_tan_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2655,
        "code": 26728,
        "name": "Xã Châu Pha",
        "codename": "xa_chau_pha",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2656,
        "code": 26732,
        "name": "Đặc khu Côn Đảo",
        "codename": "dac_khu_con_dao",
        "division_type": "đặc khu",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2657,
        "code": 26737,
        "name": "Phường Tân Định",
        "codename": "phuong_tan_dinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2658,
        "code": 26740,
        "name": "Phường Sài Gòn",
        "codename": "phuong_sai_gon",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2659,
        "code": 26743,
        "name": "Phường Bến Thành",
        "codename": "phuong_ben_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2660,
        "code": 26758,
        "name": "Phường Cầu Ông Lãnh",
        "codename": "phuong_cau_ong_lanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2661,
        "code": 26767,
        "name": "Phường An Phú Đông",
        "codename": "phuong_an_phu_dong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2662,
        "code": 26773,
        "name": "Phường Thới An",
        "codename": "phuong_thoi_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2663,
        "code": 26782,
        "name": "Phường Tân Thới Hiệp",
        "codename": "phuong_tan_thoi_hiep",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2664,
        "code": 26785,
        "name": "Phường Trung Mỹ Tây",
        "codename": "phuong_trung_my_tay",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2665,
        "code": 26791,
        "name": "Phường Đông Hưng Thuận",
        "codename": "phuong_dong_hung_thuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2666,
        "code": 26800,
        "name": "Phường Linh Xuân",
        "codename": "phuong_linh_xuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2667,
        "code": 26803,
        "name": "Phường Tam Bình",
        "codename": "phuong_tam_binh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2668,
        "code": 26809,
        "name": "Phường Hiệp Bình",
        "codename": "phuong_hiep_binh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2669,
        "code": 26824,
        "name": "Phường Thủ Đức",
        "codename": "phuong_thu_duc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2670,
        "code": 26833,
        "name": "Phường Long Bình",
        "codename": "phuong_long_binh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2671,
        "code": 26842,
        "name": "Phường Tăng Nhơn Phú",
        "codename": "phuong_tang_nhon_phu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2672,
        "code": 26848,
        "name": "Phường Phước Long",
        "codename": "phuong_phuoc_long",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2673,
        "code": 26857,
        "name": "Phường Long Phước",
        "codename": "phuong_long_phuoc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2674,
        "code": 26860,
        "name": "Phường Long Trường",
        "codename": "phuong_long_truong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2675,
        "code": 26876,
        "name": "Phường An Nhơn",
        "codename": "phuong_an_nhon",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2676,
        "code": 26878,
        "name": "Phường An Hội Đông",
        "codename": "phuong_an_hoi_dong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2677,
        "code": 26882,
        "name": "Phường An Hội Tây",
        "codename": "phuong_an_hoi_tay",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2678,
        "code": 26884,
        "name": "Phường Gò Vấp",
        "codename": "phuong_go_vap",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2679,
        "code": 26890,
        "name": "Phường Hạnh Thông",
        "codename": "phuong_hanh_thong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2680,
        "code": 26898,
        "name": "Phường Thông Tây Hội",
        "codename": "phuong_thong_tay_hoi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2681,
        "code": 26905,
        "name": "Phường Bình Lợi Trung",
        "codename": "phuong_binh_loi_trung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2682,
        "code": 26911,
        "name": "Phường Bình Quới",
        "codename": "phuong_binh_quoi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2683,
        "code": 26929,
        "name": "Phường Bình Thạnh",
        "codename": "phuong_binh_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2684,
        "code": 26944,
        "name": "Phường Gia Định",
        "codename": "phuong_gia_dinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2685,
        "code": 26956,
        "name": "Phường Thạnh Mỹ Tây",
        "codename": "phuong_thanh_my_tay",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2686,
        "code": 26968,
        "name": "Phường Tân Sơn Nhất",
        "codename": "phuong_tan_son_nhat",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2687,
        "code": 26977,
        "name": "Phường Tân Sơn Hòa",
        "codename": "phuong_tan_son_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2688,
        "code": 26983,
        "name": "Phường Bảy Hiền",
        "codename": "phuong_bay_hien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2689,
        "code": 26995,
        "name": "Phường Tân Hòa",
        "codename": "phuong_tan_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2690,
        "code": 27004,
        "name": "Phường Tân Bình",
        "codename": "phuong_tan_binh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2691,
        "code": 27007,
        "name": "Phường Tân Sơn",
        "codename": "phuong_tan_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2692,
        "code": 27013,
        "name": "Phường Tây Thạnh",
        "codename": "phuong_tay_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2693,
        "code": 27019,
        "name": "Phường Tân Sơn Nhì",
        "codename": "phuong_tan_son_nhi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2694,
        "code": 27022,
        "name": "Phường Phú Thọ Hòa",
        "codename": "phuong_phu_tho_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2695,
        "code": 27028,
        "name": "Phường Phú Thạnh",
        "codename": "phuong_phu_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2696,
        "code": 27031,
        "name": "Phường Tân Phú",
        "codename": "phuong_tan_phu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2697,
        "code": 27043,
        "name": "Phường Đức Nhuận",
        "codename": "phuong_duc_nhuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2698,
        "code": 27058,
        "name": "Phường Cầu Kiệu",
        "codename": "phuong_cau_kieu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2699,
        "code": 27073,
        "name": "Phường Phú Nhuận",
        "codename": "phuong_phu_nhuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2700,
        "code": 27094,
        "name": "Phường An Khánh",
        "codename": "phuong_an_khanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2701,
        "code": 27097,
        "name": "Phường Bình Trưng",
        "codename": "phuong_binh_trung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2702,
        "code": 27112,
        "name": "Phường Cát Lái",
        "codename": "phuong_cat_lai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2703,
        "code": 27139,
        "name": "Phường Xuân Hòa",
        "codename": "phuong_xuan_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2704,
        "code": 27142,
        "name": "Phường Nhiêu Lộc",
        "codename": "phuong_nhieu_loc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2705,
        "code": 27154,
        "name": "Phường Bàn Cờ",
        "codename": "phuong_ban_co",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2706,
        "code": 27163,
        "name": "Phường Hòa Hưng",
        "codename": "phuong_hoa_hung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2707,
        "code": 27169,
        "name": "Phường Diên Hồng",
        "codename": "phuong_dien_hong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2708,
        "code": 27190,
        "name": "Phường Vườn Lài",
        "codename": "phuong_vuon_lai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2709,
        "code": 27211,
        "name": "Phường Hòa Bình",
        "codename": "phuong_hoa_binh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2710,
        "code": 27226,
        "name": "Phường Phú Thọ",
        "codename": "phuong_phu_tho",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2711,
        "code": 27232,
        "name": "Phường Bình Thới",
        "codename": "phuong_binh_thoi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2712,
        "code": 27238,
        "name": "Phường Minh Phụng",
        "codename": "phuong_minh_phung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2713,
        "code": 27259,
        "name": "Phường Xóm Chiếu",
        "codename": "phuong_xom_chieu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2714,
        "code": 27265,
        "name": "Phường Khánh Hội",
        "codename": "phuong_khanh_hoi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2715,
        "code": 27286,
        "name": "Phường Vĩnh Hội",
        "codename": "phuong_vinh_hoi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2716,
        "code": 27301,
        "name": "Phường Chợ Quán",
        "codename": "phuong_cho_quan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2717,
        "code": 27316,
        "name": "Phường An Đông",
        "codename": "phuong_an_dong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2718,
        "code": 27343,
        "name": "Phường Chợ Lớn",
        "codename": "phuong_cho_lon",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2719,
        "code": 27349,
        "name": "Phường Phú Lâm",
        "codename": "phuong_phu_lam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2720,
        "code": 27364,
        "name": "Phường Bình Phú",
        "codename": "phuong_binh_phu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2721,
        "code": 27367,
        "name": "Phường Bình Tây",
        "codename": "phuong_binh_tay",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2722,
        "code": 27373,
        "name": "Phường Bình Tiên",
        "codename": "phuong_binh_tien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2723,
        "code": 27418,
        "name": "Phường Chánh Hưng",
        "codename": "phuong_chanh_hung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2724,
        "code": 27424,
        "name": "Phường Bình Đông",
        "codename": "phuong_binh_dong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2725,
        "code": 27427,
        "name": "Phường Phú Định",
        "codename": "phuong_phu_dinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2726,
        "code": 27439,
        "name": "Phường Bình Hưng Hòa",
        "codename": "phuong_binh_hung_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2727,
        "code": 27442,
        "name": "Phường Bình Tân",
        "codename": "phuong_binh_tan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2728,
        "code": 27448,
        "name": "Phường Bình Trị Đông",
        "codename": "phuong_binh_tri_dong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2729,
        "code": 27457,
        "name": "Phường Tân Tạo",
        "codename": "phuong_tan_tao",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2730,
        "code": 27460,
        "name": "Phường An Lạc",
        "codename": "phuong_an_lac",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2731,
        "code": 27475,
        "name": "Phường Tân Hưng",
        "codename": "phuong_tan_hung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2732,
        "code": 27478,
        "name": "Phường Tân Thuận",
        "codename": "phuong_tan_thuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2733,
        "code": 27484,
        "name": "Phường Phú Thuận",
        "codename": "phuong_phu_thuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2734,
        "code": 27487,
        "name": "Phường Tân Mỹ",
        "codename": "phuong_tan_my",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2735,
        "code": 27496,
        "name": "Xã Tân An Hội",
        "codename": "xa_tan_an_hoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2736,
        "code": 27508,
        "name": "Xã An Nhơn Tây",
        "codename": "xa_an_nhon_tay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2737,
        "code": 27511,
        "name": "Xã Nhuận Đức",
        "codename": "xa_nhuan_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2738,
        "code": 27526,
        "name": "Xã Thái Mỹ",
        "codename": "xa_thai_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2739,
        "code": 27541,
        "name": "Xã Phú Hòa Đông",
        "codename": "xa_phu_hoa_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2740,
        "code": 27544,
        "name": "Xã Bình Mỹ",
        "codename": "xa_binh_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2741,
        "code": 27553,
        "name": "Xã Củ Chi",
        "codename": "xa_cu_chi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2742,
        "code": 27559,
        "name": "Xã Hóc Môn",
        "codename": "xa_hoc_mon",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2743,
        "code": 27568,
        "name": "Xã Đông Thạnh",
        "codename": "xa_dong_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2744,
        "code": 27577,
        "name": "Xã Xuân Thới Sơn",
        "codename": "xa_xuan_thoi_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2745,
        "code": 27592,
        "name": "Xã Bà Điểm",
        "codename": "xa_ba_diem",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2746,
        "code": 27595,
        "name": "Xã Tân Nhựt",
        "codename": "xa_tan_nhut",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2747,
        "code": 27601,
        "name": "Xã Vĩnh Lộc",
        "codename": "xa_vinh_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2748,
        "code": 27604,
        "name": "Xã Tân Vĩnh Lộc",
        "codename": "xa_tan_vinh_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2749,
        "code": 27610,
        "name": "Xã Bình Lợi",
        "codename": "xa_binh_loi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2750,
        "code": 27619,
        "name": "Xã Bình Hưng",
        "codename": "xa_binh_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2751,
        "code": 27628,
        "name": "Xã Hưng Long",
        "codename": "xa_hung_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2752,
        "code": 27637,
        "name": "Xã Bình Chánh",
        "codename": "xa_binh_chanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2753,
        "code": 27655,
        "name": "Xã Nhà Bè",
        "codename": "xa_nha_be",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2754,
        "code": 27658,
        "name": "Xã Hiệp Phước",
        "codename": "xa_hiep_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2755,
        "code": 27664,
        "name": "Xã Cần Giờ",
        "codename": "xa_can_gio",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2756,
        "code": 27667,
        "name": "Xã Bình Khánh",
        "codename": "xa_binh_khanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2757,
        "code": 27673,
        "name": "Xã An Thới Đông",
        "codename": "xa_an_thoi_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2758,
        "code": 27676,
        "name": "Xã Thạnh An",
        "codename": "xa_thanh_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 79
    },
    {
        "id": 2759,
        "code": 80,
        "name": "Tỉnh Tây Ninh",
        "codename": "tay_ninh",
        "division_type": "tỉnh",
        "phone_code": 276,
        "parent_code": null
    },
    {
        "id": 2760,
        "code": 25459,
        "name": "Phường Tân Ninh",
        "codename": "phuong_tan_ninh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2761,
        "code": 25480,
        "name": "Phường Bình Minh",
        "codename": "phuong_binh_minh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2762,
        "code": 25486,
        "name": "Xã Tân Biên",
        "codename": "xa_tan_bien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2763,
        "code": 25489,
        "name": "Xã Tân Lập",
        "codename": "xa_tan_lap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2764,
        "code": 25498,
        "name": "Xã Thạnh Bình",
        "codename": "xa_thanh_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2765,
        "code": 25510,
        "name": "Xã Trà Vong",
        "codename": "xa_tra_vong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2766,
        "code": 25516,
        "name": "Xã Tân Châu",
        "codename": "xa_tan_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2767,
        "code": 25522,
        "name": "Xã Tân Đông",
        "codename": "xa_tan_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2768,
        "code": 25525,
        "name": "Xã Tân Hội",
        "codename": "xa_tan_hoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2769,
        "code": 25531,
        "name": "Xã Tân Hòa",
        "codename": "xa_tan_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2770,
        "code": 25534,
        "name": "Xã Tân Thành",
        "codename": "xa_tan_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2771,
        "code": 25549,
        "name": "Xã Tân Phú",
        "codename": "xa_tan_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2772,
        "code": 25552,
        "name": "Xã Dương Minh Châu",
        "codename": "xa_duong_minh_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2773,
        "code": 25567,
        "name": "Phường Ninh Thạnh",
        "codename": "phuong_ninh_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2774,
        "code": 25573,
        "name": "Xã Cầu Khởi",
        "codename": "xa_cau_khoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2775,
        "code": 25579,
        "name": "Xã Lộc Ninh",
        "codename": "xa_loc_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2776,
        "code": 25585,
        "name": "Xã Châu Thành",
        "codename": "xa_chau_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2777,
        "code": 25588,
        "name": "Xã Hảo Đước",
        "codename": "xa_hao_duoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2778,
        "code": 25591,
        "name": "Xã Phước Vinh",
        "codename": "xa_phuoc_vinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2779,
        "code": 25606,
        "name": "Xã Hòa Hội",
        "codename": "xa_hoa_hoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2780,
        "code": 25621,
        "name": "Xã Ninh Điền",
        "codename": "xa_ninh_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2781,
        "code": 25630,
        "name": "Phường Long Hoa",
        "codename": "phuong_long_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2782,
        "code": 25633,
        "name": "Phường Thanh Điền",
        "codename": "phuong_thanh_dien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2783,
        "code": 25645,
        "name": "Phường Hòa Thành",
        "codename": "phuong_hoa_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2784,
        "code": 25654,
        "name": "Phường Gò Dầu",
        "codename": "phuong_go_dau",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2785,
        "code": 25657,
        "name": "Xã Thạnh Đức",
        "codename": "xa_thanh_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2786,
        "code": 25663,
        "name": "Xã Phước Thạnh",
        "codename": "xa_phuoc_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2787,
        "code": 25666,
        "name": "Xã Truông Mít",
        "codename": "xa_truong_mit",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2788,
        "code": 25672,
        "name": "Phường Gia Lộc",
        "codename": "phuong_gia_loc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2789,
        "code": 25681,
        "name": "Xã Bến Cầu",
        "codename": "xa_ben_cau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2790,
        "code": 25684,
        "name": "Xã Long Chữ",
        "codename": "xa_long_chu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2791,
        "code": 25702,
        "name": "Xã Long Thuận",
        "codename": "xa_long_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2792,
        "code": 25708,
        "name": "Phường Trảng Bàng",
        "codename": "phuong_trang_bang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2793,
        "code": 25711,
        "name": "Xã Hưng Thuận",
        "codename": "xa_hung_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2794,
        "code": 25729,
        "name": "Xã Phước Chỉ",
        "codename": "xa_phuoc_chi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2795,
        "code": 25732,
        "name": "Phường An Tịnh",
        "codename": "phuong_an_tinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2796,
        "code": 27694,
        "name": "Phường Long An",
        "codename": "phuong_long_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2797,
        "code": 27712,
        "name": "Phường Tân An",
        "codename": "phuong_tan_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2798,
        "code": 27715,
        "name": "Phường Khánh Hậu",
        "codename": "phuong_khanh_hau",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2799,
        "code": 27721,
        "name": "Xã Tân Hưng",
        "codename": "xa_tan_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2800,
        "code": 27727,
        "name": "Xã Hưng Điền",
        "codename": "xa_hung_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2801,
        "code": 27736,
        "name": "Xã Vĩnh Thạnh",
        "codename": "xa_vinh_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2802,
        "code": 27748,
        "name": "Xã Vĩnh Châu",
        "codename": "xa_vinh_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2803,
        "code": 27757,
        "name": "Xã Vĩnh Hưng",
        "codename": "xa_vinh_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2804,
        "code": 27763,
        "name": "Xã Khánh Hưng",
        "codename": "xa_khanh_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2805,
        "code": 27775,
        "name": "Xã Tuyên Bình",
        "codename": "xa_tuyen_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2806,
        "code": 27787,
        "name": "Phường Kiến Tường",
        "codename": "phuong_kien_tuong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2807,
        "code": 27793,
        "name": "Xã Bình Hiệp",
        "codename": "xa_binh_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2808,
        "code": 27811,
        "name": "Xã Bình Hòa",
        "codename": "xa_binh_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2809,
        "code": 27817,
        "name": "Xã Tuyên Thạnh",
        "codename": "xa_tuyen_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2810,
        "code": 27823,
        "name": "Xã Mộc Hóa",
        "codename": "xa_moc_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2811,
        "code": 27826,
        "name": "Xã Tân Thạnh",
        "codename": "xa_tan_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2812,
        "code": 27838,
        "name": "Xã Nhơn Hòa Lập",
        "codename": "xa_nhon_hoa_lap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2813,
        "code": 27841,
        "name": "Xã Hậu Thạnh",
        "codename": "xa_hau_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2814,
        "code": 27856,
        "name": "Xã Nhơn Ninh",
        "codename": "xa_nhon_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2815,
        "code": 27865,
        "name": "Xã Thạnh Hóa",
        "codename": "xa_thanh_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2816,
        "code": 27868,
        "name": "Xã Bình Thành",
        "codename": "xa_binh_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2817,
        "code": 27877,
        "name": "Xã Thạnh Phước",
        "codename": "xa_thanh_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2818,
        "code": 27889,
        "name": "Xã Tân Tây",
        "codename": "xa_tan_tay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2819,
        "code": 27898,
        "name": "Xã Đông Thành",
        "codename": "xa_dong_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2820,
        "code": 27907,
        "name": "Xã Mỹ Quý",
        "codename": "xa_my_quy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2821,
        "code": 27925,
        "name": "Xã Đức Huệ",
        "codename": "xa_duc_hue",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2822,
        "code": 27931,
        "name": "Xã Hậu Nghĩa",
        "codename": "xa_hau_nghia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2823,
        "code": 27937,
        "name": "Xã Đức Hòa",
        "codename": "xa_duc_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2824,
        "code": 27943,
        "name": "Xã An Ninh",
        "codename": "xa_an_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2825,
        "code": 27952,
        "name": "Xã Hiệp Hòa",
        "codename": "xa_hiep_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2826,
        "code": 27964,
        "name": "Xã Đức Lập",
        "codename": "xa_duc_lap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2827,
        "code": 27976,
        "name": "Xã Mỹ Hạnh",
        "codename": "xa_my_hanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2828,
        "code": 27979,
        "name": "Xã Hòa Khánh",
        "codename": "xa_hoa_khanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2829,
        "code": 27991,
        "name": "Xã Bến Lức",
        "codename": "xa_ben_luc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2830,
        "code": 27994,
        "name": "Xã Thạnh Lợi",
        "codename": "xa_thanh_loi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2831,
        "code": 28003,
        "name": "Xã Lương Hòa",
        "codename": "xa_luong_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2832,
        "code": 28015,
        "name": "Xã Bình Đức",
        "codename": "xa_binh_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2833,
        "code": 28018,
        "name": "Xã Mỹ Yên",
        "codename": "xa_my_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2834,
        "code": 28036,
        "name": "Xã Thủ Thừa",
        "codename": "xa_thu_thua",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2835,
        "code": 28051,
        "name": "Xã Mỹ Thạnh",
        "codename": "xa_my_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2836,
        "code": 28066,
        "name": "Xã Mỹ An",
        "codename": "xa_my_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2837,
        "code": 28072,
        "name": "Xã Tân Long",
        "codename": "xa_tan_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2838,
        "code": 28075,
        "name": "Xã Tân Trụ",
        "codename": "xa_tan_tru",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2839,
        "code": 28087,
        "name": "Xã Nhựt Tảo",
        "codename": "xa_nhut_tao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2840,
        "code": 28093,
        "name": "Xã Vàm Cỏ",
        "codename": "xa_vam_co",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2841,
        "code": 28108,
        "name": "Xã Cần Đước",
        "codename": "xa_can_duoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2842,
        "code": 28114,
        "name": "Xã Rạch Kiến",
        "codename": "xa_rach_kien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2843,
        "code": 28126,
        "name": "Xã Long Cang",
        "codename": "xa_long_cang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2844,
        "code": 28132,
        "name": "Xã Mỹ Lệ",
        "codename": "xa_my_le",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2845,
        "code": 28138,
        "name": "Xã Tân Lân",
        "codename": "xa_tan_lan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2846,
        "code": 28144,
        "name": "Xã Long Hựu",
        "codename": "xa_long_huu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2847,
        "code": 28159,
        "name": "Xã Cần Giuộc",
        "codename": "xa_can_giuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2848,
        "code": 28165,
        "name": "Xã Phước Lý",
        "codename": "xa_phuoc_ly",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2849,
        "code": 28177,
        "name": "Xã Mỹ Lộc",
        "codename": "xa_my_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2850,
        "code": 28201,
        "name": "Xã Phước Vĩnh Tây",
        "codename": "xa_phuoc_vinh_tay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2851,
        "code": 28207,
        "name": "Xã Tân Tập",
        "codename": "xa_tan_tap",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2852,
        "code": 28210,
        "name": "Xã Tầm Vu",
        "codename": "xa_tam_vu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2853,
        "code": 28222,
        "name": "Xã Vĩnh Công",
        "codename": "xa_vinh_cong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2854,
        "code": 28225,
        "name": "Xã Thuận Mỹ",
        "codename": "xa_thuan_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2855,
        "code": 28243,
        "name": "Xã An Lục Long",
        "codename": "xa_an_luc_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 80
    },
    {
        "id": 2857,
        "code": 28249,
        "name": "Phường Đạo Thạnh",
        "codename": "phuong_dao_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2858,
        "code": 28261,
        "name": "Phường Mỹ Tho",
        "codename": "phuong_my_tho",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2859,
        "code": 28270,
        "name": "Phường Thới Sơn",
        "codename": "phuong_thoi_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2860,
        "code": 28273,
        "name": "Phường Mỹ Phong",
        "codename": "phuong_my_phong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2861,
        "code": 28285,
        "name": "Phường Trung An",
        "codename": "phuong_trung_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2862,
        "code": 28297,
        "name": "Phường Long Thuận",
        "codename": "phuong_long_thuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2863,
        "code": 28306,
        "name": "Phường Gò Công",
        "codename": "phuong_go_cong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2864,
        "code": 28315,
        "name": "Phường Bình Xuân",
        "codename": "phuong_binh_xuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2865,
        "code": 28321,
        "name": "Xã Tân Phước 1",
        "codename": "xa_tan_phuoc_1",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2866,
        "code": 28327,
        "name": "Xã Tân Phước 2",
        "codename": "xa_tan_phuoc_2",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2867,
        "code": 28336,
        "name": "Xã Hưng Thạnh",
        "codename": "xa_hung_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2868,
        "code": 28345,
        "name": "Xã Tân Phước 3",
        "codename": "xa_tan_phuoc_3",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2869,
        "code": 28360,
        "name": "Xã Cái Bè",
        "codename": "xa_cai_be",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2870,
        "code": 28366,
        "name": "Xã Hậu Mỹ",
        "codename": "xa_hau_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2871,
        "code": 28378,
        "name": "Xã Mỹ Thiện",
        "codename": "xa_my_thien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2872,
        "code": 28393,
        "name": "Xã Hội Cư",
        "codename": "xa_hoi_cu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2873,
        "code": 28405,
        "name": "Xã Mỹ Đức Tây",
        "codename": "xa_my_duc_tay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2874,
        "code": 28414,
        "name": "Xã Mỹ Lợi",
        "codename": "xa_my_loi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2875,
        "code": 28426,
        "name": "Xã Thanh Hưng",
        "codename": "xa_thanh_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2876,
        "code": 28429,
        "name": "Xã An Hữu",
        "codename": "xa_an_huu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2877,
        "code": 28435,
        "name": "Phường Mỹ Phước Tây",
        "codename": "phuong_my_phuoc_tay",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2878,
        "code": 28436,
        "name": "Phường Thanh Hòa",
        "codename": "phuong_thanh_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2879,
        "code": 28439,
        "name": "Phường Cai Lậy",
        "codename": "phuong_cai_lay",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2880,
        "code": 28444,
        "name": "Xã Thạnh Phú",
        "codename": "xa_thanh_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2881,
        "code": 28456,
        "name": "Xã Mỹ Thành",
        "codename": "xa_my_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2882,
        "code": 28468,
        "name": "Xã Tân Phú",
        "codename": "xa_tan_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2883,
        "code": 28471,
        "name": "Xã Bình Phú",
        "codename": "xa_binh_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2884,
        "code": 28477,
        "name": "Phường Nhị Quý",
        "codename": "phuong_nhi_quy",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2885,
        "code": 28501,
        "name": "Xã Hiệp Đức",
        "codename": "xa_hiep_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2886,
        "code": 28504,
        "name": "Xã Long Tiên",
        "codename": "xa_long_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2887,
        "code": 28516,
        "name": "Xã Ngũ Hiệp",
        "codename": "xa_ngu_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2888,
        "code": 28519,
        "name": "Xã Châu Thành",
        "codename": "xa_chau_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2889,
        "code": 28525,
        "name": "Xã Tân Hương",
        "codename": "xa_tan_huong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2890,
        "code": 28537,
        "name": "Xã Long Hưng",
        "codename": "xa_long_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2891,
        "code": 28543,
        "name": "Xã Long Định",
        "codename": "xa_long_dinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2892,
        "code": 28564,
        "name": "Xã Bình Trưng",
        "codename": "xa_binh_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2893,
        "code": 28576,
        "name": "Xã Vĩnh Kim",
        "codename": "xa_vinh_kim",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2894,
        "code": 28582,
        "name": "Xã Kim Sơn",
        "codename": "xa_kim_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2895,
        "code": 28594,
        "name": "Xã Chợ Gạo",
        "codename": "xa_cho_gao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2896,
        "code": 28603,
        "name": "Xã Mỹ Tịnh An",
        "codename": "xa_my_tinh_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2897,
        "code": 28615,
        "name": "Xã Lương Hòa Lạc",
        "codename": "xa_luong_hoa_lac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2898,
        "code": 28627,
        "name": "Xã Tân Thuận Bình",
        "codename": "xa_tan_thuan_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2899,
        "code": 28633,
        "name": "Xã An Thạnh Thủy",
        "codename": "xa_an_thanh_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2900,
        "code": 28648,
        "name": "Xã Bình Ninh",
        "codename": "xa_binh_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2901,
        "code": 28651,
        "name": "Xã Vĩnh Bình",
        "codename": "xa_vinh_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2902,
        "code": 28660,
        "name": "Xã Đồng Sơn",
        "codename": "xa_dong_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2903,
        "code": 28663,
        "name": "Xã Phú Thành",
        "codename": "xa_phu_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2904,
        "code": 28678,
        "name": "Xã Vĩnh Hựu",
        "codename": "xa_vinh_huu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2905,
        "code": 28687,
        "name": "Xã Long Bình",
        "codename": "xa_long_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2906,
        "code": 28693,
        "name": "Xã Tân Thới",
        "codename": "xa_tan_thoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2907,
        "code": 28696,
        "name": "Xã Tân Phú Đông",
        "codename": "xa_tan_phu_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2908,
        "code": 28702,
        "name": "Xã Tân Hòa",
        "codename": "xa_tan_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2909,
        "code": 28720,
        "name": "Xã Gia Thuận",
        "codename": "xa_gia_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2910,
        "code": 28723,
        "name": "Xã Tân Đông",
        "codename": "xa_tan_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2911,
        "code": 28729,
        "name": "Phường Sơn Qui",
        "codename": "phuong_son_qui",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2912,
        "code": 28738,
        "name": "Xã Tân Điền",
        "codename": "xa_tan_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2913,
        "code": 28747,
        "name": "Xã Gò Công Đông",
        "codename": "xa_go_cong_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2914,
        "code": 29869,
        "name": "Phường Cao Lãnh",
        "codename": "phuong_cao_lanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2915,
        "code": 29884,
        "name": "Phường Mỹ Ngãi",
        "codename": "phuong_my_ngai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2916,
        "code": 29888,
        "name": "Phường Mỹ Trà",
        "codename": "phuong_my_tra",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2917,
        "code": 29905,
        "name": "Phường Sa Đéc",
        "codename": "phuong_sa_dec",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2918,
        "code": 29926,
        "name": "Xã Tân Hồng",
        "codename": "xa_tan_hong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2919,
        "code": 29929,
        "name": "Xã Tân Hộ Cơ",
        "codename": "xa_tan_ho_co",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2920,
        "code": 29938,
        "name": "Xã Tân Thành",
        "codename": "xa_tan_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2921,
        "code": 29944,
        "name": "Xã An Phước",
        "codename": "xa_an_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2922,
        "code": 29954,
        "name": "Phường An Bình",
        "codename": "phuong_an_binh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2923,
        "code": 29955,
        "name": "Phường Hồng Ngự",
        "codename": "phuong_hong_ngu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2924,
        "code": 29971,
        "name": "Xã Thường Phước",
        "codename": "xa_thuong_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2925,
        "code": 29978,
        "name": "Phường Thường Lạc",
        "codename": "phuong_thuong_lac",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2926,
        "code": 29983,
        "name": "Xã Long Khánh",
        "codename": "xa_long_khanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2927,
        "code": 29992,
        "name": "Xã Long Phú Thuận",
        "codename": "xa_long_phu_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2928,
        "code": 30001,
        "name": "Xã Tràm Chim",
        "codename": "xa_tram_chim",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2929,
        "code": 30010,
        "name": "Xã Tam Nông",
        "codename": "xa_tam_nong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2930,
        "code": 30019,
        "name": "Xã An Hòa",
        "codename": "xa_an_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2931,
        "code": 30025,
        "name": "Xã Phú Cường",
        "codename": "xa_phu_cuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2932,
        "code": 30028,
        "name": "Xã An Long",
        "codename": "xa_an_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2933,
        "code": 30034,
        "name": "Xã Phú Thọ",
        "codename": "xa_phu_tho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2934,
        "code": 30037,
        "name": "Xã Tháp Mười",
        "codename": "xa_thap_muoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2935,
        "code": 30043,
        "name": "Xã Phương Thịnh",
        "codename": "xa_phuong_thinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2936,
        "code": 30046,
        "name": "Xã Trường Xuân",
        "codename": "xa_truong_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2937,
        "code": 30055,
        "name": "Xã Mỹ Quí",
        "codename": "xa_my_qui",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2938,
        "code": 30061,
        "name": "Xã Đốc Binh Kiều",
        "codename": "xa_doc_binh_kieu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2939,
        "code": 30073,
        "name": "Xã Thanh Mỹ",
        "codename": "xa_thanh_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2940,
        "code": 30076,
        "name": "Xã Mỹ Thọ",
        "codename": "xa_my_tho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2941,
        "code": 30085,
        "name": "Xã Ba Sao",
        "codename": "xa_ba_sao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2942,
        "code": 30088,
        "name": "Xã Phong Mỹ",
        "codename": "xa_phong_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2943,
        "code": 30112,
        "name": "Xã Mỹ Hiệp",
        "codename": "xa_my_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2944,
        "code": 30118,
        "name": "Xã Bình Hàng Trung",
        "codename": "xa_binh_hang_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2945,
        "code": 30130,
        "name": "Xã Thanh Bình",
        "codename": "xa_thanh_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2946,
        "code": 30154,
        "name": "Xã Tân Long",
        "codename": "xa_tan_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2947,
        "code": 30157,
        "name": "Xã Tân Thạnh",
        "codename": "xa_tan_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2948,
        "code": 30163,
        "name": "Xã Bình Thành",
        "codename": "xa_binh_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2949,
        "code": 30169,
        "name": "Xã Lấp Vò",
        "codename": "xa_lap_vo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2950,
        "code": 30178,
        "name": "Xã Mỹ An Hưng",
        "codename": "xa_my_an_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2951,
        "code": 30184,
        "name": "Xã Tân Khánh Trung",
        "codename": "xa_tan_khanh_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2952,
        "code": 30208,
        "name": "Xã Hòa Long",
        "codename": "xa_hoa_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2953,
        "code": 30214,
        "name": "Xã Tân Dương",
        "codename": "xa_tan_duong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2954,
        "code": 30226,
        "name": "Xã Lai Vung",
        "codename": "xa_lai_vung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2955,
        "code": 30235,
        "name": "Xã Phong Hòa",
        "codename": "xa_phong_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2956,
        "code": 30244,
        "name": "Xã Phú Hựu",
        "codename": "xa_phu_huu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2957,
        "code": 30253,
        "name": "Xã Tân Nhuận Đông",
        "codename": "xa_tan_nhuan_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2958,
        "code": 30259,
        "name": "Xã Tân Phú Trung",
        "codename": "xa_tan_phu_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 82
    },
    {
        "id": 2959,
        "code": 86,
        "name": "Tỉnh Vĩnh Long",
        "codename": "vinh_long",
        "division_type": "tỉnh",
        "phone_code": 270,
        "parent_code": null
    },
    {
        "id": 2960,
        "code": 28756,
        "name": "Phường Phú Khương",
        "codename": "phuong_phu_khuong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2961,
        "code": 28777,
        "name": "Phường An Hội",
        "codename": "phuong_an_hoi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2962,
        "code": 28783,
        "name": "Phường Sơn Đông",
        "codename": "phuong_son_dong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2963,
        "code": 28789,
        "name": "Phường Bến Tre",
        "codename": "phuong_ben_tre",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2964,
        "code": 28807,
        "name": "Xã Giao Long",
        "codename": "xa_giao_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2965,
        "code": 28810,
        "name": "Xã Phú Túc",
        "codename": "xa_phu_tuc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2966,
        "code": 28840,
        "name": "Xã Tân Phú",
        "codename": "xa_tan_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2967,
        "code": 28858,
        "name": "Phường Phú Tân",
        "codename": "phuong_phu_tan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2968,
        "code": 28861,
        "name": "Xã Tiên Thủy",
        "codename": "xa_tien_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2969,
        "code": 28870,
        "name": "Xã Chợ Lách",
        "codename": "xa_cho_lach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2970,
        "code": 28879,
        "name": "Xã Phú Phụng",
        "codename": "xa_phu_phung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2971,
        "code": 28894,
        "name": "Xã Vĩnh Thành",
        "codename": "xa_vinh_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2972,
        "code": 28901,
        "name": "Xã Hưng Khánh Trung",
        "codename": "xa_hung_khanh_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2973,
        "code": 28903,
        "name": "Xã Mỏ Cày",
        "codename": "xa_mo_cay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2974,
        "code": 28915,
        "name": "Xã Phước Mỹ Trung",
        "codename": "xa_phuoc_my_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2975,
        "code": 28921,
        "name": "Xã Tân Thành Bình",
        "codename": "xa_tan_thanh_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2976,
        "code": 28945,
        "name": "Xã Đồng Khởi",
        "codename": "xa_dong_khoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2977,
        "code": 28948,
        "name": "Xã Nhuận Phú Tân",
        "codename": "xa_nhuan_phu_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2978,
        "code": 28957,
        "name": "Xã An Định",
        "codename": "xa_an_dinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2979,
        "code": 28969,
        "name": "Xã Thành Thới",
        "codename": "xa_thanh_thoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2980,
        "code": 28981,
        "name": "Xã Hương Mỹ",
        "codename": "xa_huong_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2981,
        "code": 28984,
        "name": "Xã Giồng Trôm",
        "codename": "xa_giong_trom",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2982,
        "code": 28987,
        "name": "Xã Lương Hòa",
        "codename": "xa_luong_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2983,
        "code": 28993,
        "name": "Xã Lương Phú",
        "codename": "xa_luong_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2984,
        "code": 28996,
        "name": "Xã Châu Hòa",
        "codename": "xa_chau_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2985,
        "code": 29020,
        "name": "Xã Phước Long",
        "codename": "xa_phuoc_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2986,
        "code": 29029,
        "name": "Xã Tân Hào",
        "codename": "xa_tan_hao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2987,
        "code": 29044,
        "name": "Xã Hưng Nhượng",
        "codename": "xa_hung_nhuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2988,
        "code": 29050,
        "name": "Xã Bình Đại",
        "codename": "xa_binh_dai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2989,
        "code": 29062,
        "name": "Xã Phú Thuận",
        "codename": "xa_phu_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2990,
        "code": 29077,
        "name": "Xã Lộc Thuận",
        "codename": "xa_loc_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2991,
        "code": 29083,
        "name": "Xã Châu Hưng",
        "codename": "xa_chau_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2992,
        "code": 29089,
        "name": "Xã Thạnh Trị",
        "codename": "xa_thanh_tri",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2993,
        "code": 29104,
        "name": "Xã Thạnh Phước",
        "codename": "xa_thanh_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2994,
        "code": 29107,
        "name": "Xã Thới Thuận",
        "codename": "xa_thoi_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2995,
        "code": 29110,
        "name": "Xã Ba Tri",
        "codename": "xa_ba_tri",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2996,
        "code": 29122,
        "name": "Xã Mỹ Chánh Hòa",
        "codename": "xa_my_chanh_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2997,
        "code": 29125,
        "name": "Xã Bảo Thạnh",
        "codename": "xa_bao_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2998,
        "code": 29137,
        "name": "Xã Tân Xuân",
        "codename": "xa_tan_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 2999,
        "code": 29143,
        "name": "Xã An Ngãi Trung",
        "codename": "xa_an_ngai_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3000,
        "code": 29158,
        "name": "Xã An Hiệp",
        "codename": "xa_an_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3001,
        "code": 29167,
        "name": "Xã Tân Thủy",
        "codename": "xa_tan_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3002,
        "code": 29182,
        "name": "Xã Thạnh Phú",
        "codename": "xa_thanh_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3003,
        "code": 29191,
        "name": "Xã Quới Điền",
        "codename": "xa_quoi_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3004,
        "code": 29194,
        "name": "Xã Đại Điền",
        "codename": "xa_dai_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3005,
        "code": 29221,
        "name": "Xã Thạnh Hải",
        "codename": "xa_thanh_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3006,
        "code": 29224,
        "name": "Xã An Qui",
        "codename": "xa_an_qui",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3007,
        "code": 29227,
        "name": "Xã Thạnh Phong",
        "codename": "xa_thanh_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3008,
        "code": 29242,
        "name": "Phường Trà Vinh",
        "codename": "phuong_tra_vinh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3009,
        "code": 29254,
        "name": "Phường Nguyệt Hóa",
        "codename": "phuong_nguyet_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3010,
        "code": 29263,
        "name": "Phường Long Đức",
        "codename": "phuong_long_duc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3011,
        "code": 29266,
        "name": "Xã Càng Long",
        "codename": "xa_cang_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3012,
        "code": 29275,
        "name": "Xã An Trường",
        "codename": "xa_an_truong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3013,
        "code": 29278,
        "name": "Xã Tân An",
        "codename": "xa_tan_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3014,
        "code": 29287,
        "name": "Xã Bình Phú",
        "codename": "xa_binh_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3015,
        "code": 29302,
        "name": "Xã Nhị Long",
        "codename": "xa_nhi_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3016,
        "code": 29308,
        "name": "Xã Cầu Kè",
        "codename": "xa_cau_ke",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3017,
        "code": 29317,
        "name": "Xã An Phú Tân",
        "codename": "xa_an_phu_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3018,
        "code": 29329,
        "name": "Xã Phong Thạnh",
        "codename": "xa_phong_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3019,
        "code": 29335,
        "name": "Xã Tam Ngãi",
        "codename": "xa_tam_ngai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3020,
        "code": 29341,
        "name": "Xã Tiểu Cần",
        "codename": "xa_tieu_can",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3021,
        "code": 29362,
        "name": "Xã Hùng Hòa",
        "codename": "xa_hung_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3022,
        "code": 29365,
        "name": "Xã Tập Ngãi",
        "codename": "xa_tap_ngai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3023,
        "code": 29371,
        "name": "Xã Tân Hòa",
        "codename": "xa_tan_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3024,
        "code": 29374,
        "name": "Xã Châu Thành",
        "codename": "xa_chau_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3025,
        "code": 29386,
        "name": "Xã Song Lộc",
        "codename": "xa_song_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3026,
        "code": 29398,
        "name": "Phường Hòa Thuận",
        "codename": "phuong_hoa_thuan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3027,
        "code": 29407,
        "name": "Xã Hưng Mỹ",
        "codename": "xa_hung_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3028,
        "code": 29410,
        "name": "Xã Hòa Minh",
        "codename": "xa_hoa_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3029,
        "code": 29413,
        "name": "Xã Long Hòa",
        "codename": "xa_long_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3030,
        "code": 29416,
        "name": "Xã Cầu Ngang",
        "codename": "xa_cau_ngang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3031,
        "code": 29419,
        "name": "Xã Mỹ Long",
        "codename": "xa_my_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3032,
        "code": 29431,
        "name": "Xã Vinh Kim",
        "codename": "xa_vinh_kim",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3033,
        "code": 29446,
        "name": "Xã Nhị Trường",
        "codename": "xa_nhi_truong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3034,
        "code": 29455,
        "name": "Xã Hiệp Mỹ",
        "codename": "xa_hiep_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3035,
        "code": 29461,
        "name": "Xã Trà Cú",
        "codename": "xa_tra_cu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3036,
        "code": 29467,
        "name": "Xã Tập Sơn",
        "codename": "xa_tap_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3037,
        "code": 29476,
        "name": "Xã Lưu Nghiệp Anh",
        "codename": "xa_luu_nghiep_anh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3038,
        "code": 29489,
        "name": "Xã Hàm Giang",
        "codename": "xa_ham_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3039,
        "code": 29491,
        "name": "Xã Đại An",
        "codename": "xa_dai_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3040,
        "code": 29497,
        "name": "Xã Đôn Châu",
        "codename": "xa_don_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3041,
        "code": 29506,
        "name": "Xã Long Hiệp",
        "codename": "xa_long_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3042,
        "code": 29512,
        "name": "Phường Duyên Hải",
        "codename": "phuong_duyen_hai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3043,
        "code": 29513,
        "name": "Xã Long Thành",
        "codename": "xa_long_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3044,
        "code": 29516,
        "name": "Phường Trường Long Hòa",
        "codename": "phuong_truong_long_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3045,
        "code": 29518,
        "name": "Xã Long Hữu",
        "codename": "xa_long_huu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3046,
        "code": 29530,
        "name": "Xã Ngũ Lạc",
        "codename": "xa_ngu_lac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3047,
        "code": 29533,
        "name": "Xã Long Vĩnh",
        "codename": "xa_long_vinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3048,
        "code": 29536,
        "name": "Xã Đông Hải",
        "codename": "xa_dong_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3049,
        "code": 29551,
        "name": "Phường Long Châu",
        "codename": "phuong_long_chau",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3050,
        "code": 29557,
        "name": "Phường Phước Hậu",
        "codename": "phuong_phuoc_hau",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3051,
        "code": 29566,
        "name": "Phường Tân Ngãi",
        "codename": "phuong_tan_ngai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3052,
        "code": 29584,
        "name": "Xã An Bình",
        "codename": "xa_an_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3053,
        "code": 29590,
        "name": "Phường Thanh Đức",
        "codename": "phuong_thanh_duc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3054,
        "code": 29593,
        "name": "Phường Tân Hạnh",
        "codename": "phuong_tan_hanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3055,
        "code": 29602,
        "name": "Xã Long Hồ",
        "codename": "xa_long_ho",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3056,
        "code": 29611,
        "name": "Xã Phú Quới",
        "codename": "xa_phu_quoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3057,
        "code": 29623,
        "name": "Xã Nhơn Phú",
        "codename": "xa_nhon_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3058,
        "code": 29638,
        "name": "Xã Bình Phước",
        "codename": "xa_binh_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3059,
        "code": 29641,
        "name": "Xã Cái Nhum",
        "codename": "xa_cai_nhum",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3060,
        "code": 29653,
        "name": "Xã Tân Long Hội",
        "codename": "xa_tan_long_hoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3061,
        "code": 29659,
        "name": "Xã Trung Thành",
        "codename": "xa_trung_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3062,
        "code": 29668,
        "name": "Xã Quới An",
        "codename": "xa_quoi_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3063,
        "code": 29677,
        "name": "Xã Quới Thiện",
        "codename": "xa_quoi_thien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3064,
        "code": 29683,
        "name": "Xã Trung Hiệp",
        "codename": "xa_trung_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3065,
        "code": 29698,
        "name": "Xã Trung Ngãi",
        "codename": "xa_trung_ngai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3066,
        "code": 29701,
        "name": "Xã Hiếu Phụng",
        "codename": "xa_hieu_phung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3067,
        "code": 29713,
        "name": "Xã Hiếu Thành",
        "codename": "xa_hieu_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3068,
        "code": 29719,
        "name": "Xã Tam Bình",
        "codename": "xa_tam_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3069,
        "code": 29728,
        "name": "Xã Cái Ngang",
        "codename": "xa_cai_ngang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3070,
        "code": 29734,
        "name": "Xã Hòa Hiệp",
        "codename": "xa_hoa_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3071,
        "code": 29740,
        "name": "Xã Song Phú",
        "codename": "xa_song_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3072,
        "code": 29767,
        "name": "Xã Ngãi Tứ",
        "codename": "xa_ngai_tu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3073,
        "code": 29770,
        "name": "Phường Cái Vồn",
        "codename": "phuong_cai_von",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3074,
        "code": 29771,
        "name": "Phường Bình Minh",
        "codename": "phuong_binh_minh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3075,
        "code": 29785,
        "name": "Xã Tân Lược",
        "codename": "xa_tan_luoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3076,
        "code": 29788,
        "name": "Xã Mỹ Thuận",
        "codename": "xa_my_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3077,
        "code": 29800,
        "name": "Xã Tân Quới",
        "codename": "xa_tan_quoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3078,
        "code": 29812,
        "name": "Phường Đông Thành",
        "codename": "phuong_dong_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3079,
        "code": 29821,
        "name": "Xã Trà Ôn",
        "codename": "xa_tra_on",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3080,
        "code": 29830,
        "name": "Xã Hòa Bình",
        "codename": "xa_hoa_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3081,
        "code": 29836,
        "name": "Xã Trà Côn",
        "codename": "xa_tra_con",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3082,
        "code": 29845,
        "name": "Xã Vĩnh Xuân",
        "codename": "xa_vinh_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3083,
        "code": 29857,
        "name": "Xã Lục Sĩ Thành",
        "codename": "xa_luc_si_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 86
    },
    {
        "id": 3085,
        "code": 30292,
        "name": "Phường Bình Đức",
        "codename": "phuong_binh_duc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3086,
        "code": 30301,
        "name": "Phường Mỹ Thới",
        "codename": "phuong_my_thoi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3087,
        "code": 30307,
        "name": "Phường Long Xuyên",
        "codename": "phuong_long_xuyen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3088,
        "code": 30313,
        "name": "Xã Mỹ Hòa Hưng",
        "codename": "xa_my_hoa_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3089,
        "code": 30316,
        "name": "Phường Châu Đốc",
        "codename": "phuong_chau_doc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3090,
        "code": 30325,
        "name": "Phường Vĩnh Tế",
        "codename": "phuong_vinh_te",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3091,
        "code": 30337,
        "name": "Xã An Phú",
        "codename": "xa_an_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3092,
        "code": 30341,
        "name": "Xã Khánh Bình",
        "codename": "xa_khanh_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3093,
        "code": 30346,
        "name": "Xã Nhơn Hội",
        "codename": "xa_nhon_hoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3094,
        "code": 30352,
        "name": "Xã Phú Hữu",
        "codename": "xa_phu_huu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3095,
        "code": 30367,
        "name": "Xã Vĩnh Hậu",
        "codename": "xa_vinh_hau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3096,
        "code": 30376,
        "name": "Phường Tân Châu",
        "codename": "phuong_tan_chau",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3097,
        "code": 30377,
        "name": "Phường Long Phú",
        "codename": "phuong_long_phu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3098,
        "code": 30385,
        "name": "Xã Vĩnh Xương",
        "codename": "xa_vinh_xuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3099,
        "code": 30388,
        "name": "Xã Tân An",
        "codename": "xa_tan_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3100,
        "code": 30403,
        "name": "Xã Châu Phong",
        "codename": "xa_chau_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3101,
        "code": 30406,
        "name": "Xã Phú Tân",
        "codename": "xa_phu_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3102,
        "code": 30409,
        "name": "Xã Chợ Vàm",
        "codename": "xa_cho_vam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3103,
        "code": 30421,
        "name": "Xã Phú Lâm",
        "codename": "xa_phu_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3104,
        "code": 30430,
        "name": "Xã Hòa Lạc",
        "codename": "xa_hoa_lac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3105,
        "code": 30436,
        "name": "Xã Phú An",
        "codename": "xa_phu_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3106,
        "code": 30445,
        "name": "Xã Bình Thạnh Đông",
        "codename": "xa_binh_thanh_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3107,
        "code": 30463,
        "name": "Xã Châu Phú",
        "codename": "xa_chau_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3108,
        "code": 30469,
        "name": "Xã Mỹ Đức",
        "codename": "xa_my_duc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3109,
        "code": 30478,
        "name": "Xã Vĩnh Thạnh Trung",
        "codename": "xa_vinh_thanh_trung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3110,
        "code": 30481,
        "name": "Xã Thạnh Mỹ Tây",
        "codename": "xa_thanh_my_tay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3111,
        "code": 30487,
        "name": "Xã Bình Mỹ",
        "codename": "xa_binh_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3112,
        "code": 30502,
        "name": "Phường Thới Sơn",
        "codename": "phuong_thoi_son",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3113,
        "code": 30505,
        "name": "Phường Chi Lăng",
        "codename": "phuong_chi_lang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3114,
        "code": 30520,
        "name": "Phường Tịnh Biên",
        "codename": "phuong_tinh_bien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3115,
        "code": 30526,
        "name": "Xã An Cư",
        "codename": "xa_an_cu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3116,
        "code": 30538,
        "name": "Xã Núi Cấm",
        "codename": "xa_nui_cam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3117,
        "code": 30544,
        "name": "Xã Tri Tôn",
        "codename": "xa_tri_ton",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3118,
        "code": 30547,
        "name": "Xã Ba Chúc",
        "codename": "xa_ba_chuc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3119,
        "code": 30568,
        "name": "Xã Vĩnh Gia",
        "codename": "xa_vinh_gia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3120,
        "code": 30577,
        "name": "Xã Ô Lâm",
        "codename": "xa_o_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3121,
        "code": 30580,
        "name": "Xã Cô Tô",
        "codename": "xa_co_to",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3122,
        "code": 30589,
        "name": "Xã An Châu",
        "codename": "xa_an_chau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3123,
        "code": 30595,
        "name": "Xã Cần Đăng",
        "codename": "xa_can_dang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3124,
        "code": 30604,
        "name": "Xã Vĩnh An",
        "codename": "xa_vinh_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3125,
        "code": 30607,
        "name": "Xã Bình Hòa",
        "codename": "xa_binh_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3126,
        "code": 30619,
        "name": "Xã Vĩnh Hanh",
        "codename": "xa_vinh_hanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3127,
        "code": 30628,
        "name": "Xã Chợ Mới",
        "codename": "xa_cho_moi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3128,
        "code": 30631,
        "name": "Xã Long Điền",
        "codename": "xa_long_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3129,
        "code": 30643,
        "name": "Xã Cù Lao Giêng",
        "codename": "xa_cu_lao_gieng",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3130,
        "code": 30658,
        "name": "Xã Nhơn Mỹ",
        "codename": "xa_nhon_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3131,
        "code": 30664,
        "name": "Xã Long Kiến",
        "codename": "xa_long_kien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3132,
        "code": 30673,
        "name": "Xã Hội An",
        "codename": "xa_hoi_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3133,
        "code": 30682,
        "name": "Xã Thoại Sơn",
        "codename": "xa_thoai_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3134,
        "code": 30685,
        "name": "Xã Phú Hòa",
        "codename": "xa_phu_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3135,
        "code": 30688,
        "name": "Xã Óc Eo",
        "codename": "xa_oc_eo",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3136,
        "code": 30691,
        "name": "Xã Tây Phú",
        "codename": "xa_tay_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3137,
        "code": 30697,
        "name": "Xã Vĩnh Trạch",
        "codename": "xa_vinh_trach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3138,
        "code": 30709,
        "name": "Xã Định Mỹ",
        "codename": "xa_dinh_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3139,
        "code": 30742,
        "name": "Phường Rạch Giá",
        "codename": "phuong_rach_gia",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3140,
        "code": 30760,
        "name": "Phường Vĩnh Thông",
        "codename": "phuong_vinh_thong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3141,
        "code": 30766,
        "name": "Phường Tô Châu",
        "codename": "phuong_to_chau",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3142,
        "code": 30769,
        "name": "Phường Hà Tiên",
        "codename": "phuong_ha_tien",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3143,
        "code": 30781,
        "name": "Xã Tiên Hải",
        "codename": "xa_tien_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3144,
        "code": 30787,
        "name": "Xã Kiên Lương",
        "codename": "xa_kien_luong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3145,
        "code": 30790,
        "name": "Xã Hòa Điền",
        "codename": "xa_hoa_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3146,
        "code": 30793,
        "name": "Xã Vĩnh Điều",
        "codename": "xa_vinh_dieu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3147,
        "code": 30796,
        "name": "Xã Giang Thành",
        "codename": "xa_giang_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3148,
        "code": 30811,
        "name": "Xã Sơn Hải",
        "codename": "xa_son_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3149,
        "code": 30814,
        "name": "Xã Hòn Nghệ",
        "codename": "xa_hon_nghe",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3150,
        "code": 30817,
        "name": "Xã Hòn Đất",
        "codename": "xa_hon_dat",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3151,
        "code": 30823,
        "name": "Xã Bình Sơn",
        "codename": "xa_binh_son",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3152,
        "code": 30826,
        "name": "Xã Bình Giang",
        "codename": "xa_binh_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3153,
        "code": 30835,
        "name": "Xã Sơn Kiên",
        "codename": "xa_son_kien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3154,
        "code": 30838,
        "name": "Xã Mỹ Thuận",
        "codename": "xa_my_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3155,
        "code": 30850,
        "name": "Xã Tân Hiệp",
        "codename": "xa_tan_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3156,
        "code": 30856,
        "name": "Xã Tân Hội",
        "codename": "xa_tan_hoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3157,
        "code": 30874,
        "name": "Xã Thạnh Đông",
        "codename": "xa_thanh_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3158,
        "code": 30880,
        "name": "Xã Châu Thành",
        "codename": "xa_chau_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3159,
        "code": 30886,
        "name": "Xã Thạnh Lộc",
        "codename": "xa_thanh_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3160,
        "code": 30898,
        "name": "Xã Bình An",
        "codename": "xa_binh_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3161,
        "code": 30904,
        "name": "Xã Giồng Riềng",
        "codename": "xa_giong_rieng",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3162,
        "code": 30910,
        "name": "Xã Thạnh Hưng",
        "codename": "xa_thanh_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3163,
        "code": 30928,
        "name": "Xã Ngọc Chúc",
        "codename": "xa_ngoc_chuc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3164,
        "code": 30934,
        "name": "Xã Hòa Hưng",
        "codename": "xa_hoa_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3165,
        "code": 30943,
        "name": "Xã Long Thạnh",
        "codename": "xa_long_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3166,
        "code": 30949,
        "name": "Xã Hòa Thuận",
        "codename": "xa_hoa_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3167,
        "code": 30952,
        "name": "Xã Gò Quao",
        "codename": "xa_go_quao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3168,
        "code": 30958,
        "name": "Xã Định Hòa",
        "codename": "xa_dinh_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3169,
        "code": 30970,
        "name": "Xã Vĩnh Hòa Hưng",
        "codename": "xa_vinh_hoa_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3170,
        "code": 30982,
        "name": "Xã Vĩnh Tuy",
        "codename": "xa_vinh_tuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3171,
        "code": 30985,
        "name": "Xã An Biên",
        "codename": "xa_an_bien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3172,
        "code": 30988,
        "name": "Xã Tây Yên",
        "codename": "xa_tay_yen",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3173,
        "code": 31006,
        "name": "Xã Đông Thái",
        "codename": "xa_dong_thai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3174,
        "code": 31012,
        "name": "Xã Vĩnh Hòa",
        "codename": "xa_vinh_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3175,
        "code": 31018,
        "name": "Xã An Minh",
        "codename": "xa_an_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3176,
        "code": 31024,
        "name": "Xã Đông Hòa",
        "codename": "xa_dong_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3177,
        "code": 31027,
        "name": "Xã U Minh Thượng",
        "codename": "xa_u_minh_thuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3178,
        "code": 31031,
        "name": "Xã Tân Thạnh",
        "codename": "xa_tan_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3179,
        "code": 31036,
        "name": "Xã Đông Hưng",
        "codename": "xa_dong_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3180,
        "code": 31042,
        "name": "Xã Vân Khánh",
        "codename": "xa_van_khanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3181,
        "code": 31051,
        "name": "Xã Vĩnh Phong",
        "codename": "xa_vinh_phong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3182,
        "code": 31064,
        "name": "Xã Vĩnh Bình",
        "codename": "xa_vinh_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3183,
        "code": 31069,
        "name": "Xã Vĩnh Thuận",
        "codename": "xa_vinh_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3184,
        "code": 31078,
        "name": "Đặc khu Phú Quốc",
        "codename": "dac_khu_phu_quoc",
        "division_type": "đặc khu",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3185,
        "code": 31105,
        "name": "Đặc khu Thổ Châu",
        "codename": "dac_khu_tho_chau",
        "division_type": "đặc khu",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3186,
        "code": 31108,
        "name": "Đặc khu Kiên Hải",
        "codename": "dac_khu_kien_hai",
        "division_type": "đặc khu",
        "phone_code": null,
        "parent_code": 91
    },
    {
        "id": 3187,
        "code": 92,
        "name": "Thành phố Cần Thơ",
        "codename": "can_tho",
        "division_type": "thành phố trung ương",
        "phone_code": 292,
        "parent_code": null
    },
    {
        "id": 3188,
        "code": 31120,
        "name": "Phường Cái Khế",
        "codename": "phuong_cai_khe",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3189,
        "code": 31135,
        "name": "Phường Ninh Kiều",
        "codename": "phuong_ninh_kieu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3190,
        "code": 31147,
        "name": "Phường Tân An",
        "codename": "phuong_tan_an",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3191,
        "code": 31150,
        "name": "Phường An Bình",
        "codename": "phuong_an_binh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3192,
        "code": 31153,
        "name": "Phường Ô Môn",
        "codename": "phuong_o_mon",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3193,
        "code": 31157,
        "name": "Phường Thới Long",
        "codename": "phuong_thoi_long",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3194,
        "code": 31162,
        "name": "Phường Phước Thới",
        "codename": "phuong_phuoc_thoi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3195,
        "code": 31168,
        "name": "Phường Bình Thủy",
        "codename": "phuong_binh_thuy",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3196,
        "code": 31174,
        "name": "Phường Thới An Đông",
        "codename": "phuong_thoi_an_dong",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3197,
        "code": 31183,
        "name": "Phường Long Tuyền",
        "codename": "phuong_long_tuyen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3198,
        "code": 31186,
        "name": "Phường Cái Răng",
        "codename": "phuong_cai_rang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3199,
        "code": 31201,
        "name": "Phường Hưng Phú",
        "codename": "phuong_hung_phu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3200,
        "code": 31207,
        "name": "Phường Thốt Nốt",
        "codename": "phuong_thot_not",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3201,
        "code": 31213,
        "name": "Phường Tân Lộc",
        "codename": "phuong_tan_loc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3202,
        "code": 31217,
        "name": "Phường Trung Nhứt",
        "codename": "phuong_trung_nhut",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3203,
        "code": 31228,
        "name": "Phường Thuận Hưng",
        "codename": "phuong_thuan_hung",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3204,
        "code": 31231,
        "name": "Xã Thạnh An",
        "codename": "xa_thanh_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3205,
        "code": 31232,
        "name": "Xã Vĩnh Thạnh",
        "codename": "xa_vinh_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3206,
        "code": 31237,
        "name": "Xã Vĩnh Trinh",
        "codename": "xa_vinh_trinh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3207,
        "code": 31246,
        "name": "Xã Thạnh Quới",
        "codename": "xa_thanh_quoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3208,
        "code": 31249,
        "name": "Xã Thạnh Phú",
        "codename": "xa_thanh_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3209,
        "code": 31255,
        "name": "Xã Trung Hưng",
        "codename": "xa_trung_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3210,
        "code": 31258,
        "name": "Xã Thới Lai",
        "codename": "xa_thoi_lai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3211,
        "code": 31261,
        "name": "Xã Cờ Đỏ",
        "codename": "xa_co_do",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3212,
        "code": 31264,
        "name": "Xã Thới Hưng",
        "codename": "xa_thoi_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3213,
        "code": 31273,
        "name": "Xã Đông Hiệp",
        "codename": "xa_dong_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3214,
        "code": 31282,
        "name": "Xã Đông Thuận",
        "codename": "xa_dong_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3215,
        "code": 31288,
        "name": "Xã Trường Thành",
        "codename": "xa_truong_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3216,
        "code": 31294,
        "name": "Xã Trường Xuân",
        "codename": "xa_truong_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3217,
        "code": 31299,
        "name": "Xã Phong Điền",
        "codename": "xa_phong_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3218,
        "code": 31309,
        "name": "Xã Trường Long",
        "codename": "xa_truong_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3219,
        "code": 31315,
        "name": "Xã Nhơn Ái",
        "codename": "xa_nhon_ai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3220,
        "code": 31321,
        "name": "Phường Vị Thanh",
        "codename": "phuong_vi_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3221,
        "code": 31333,
        "name": "Phường Vị Tân",
        "codename": "phuong_vi_tan",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3222,
        "code": 31338,
        "name": "Xã Hỏa Lựu",
        "codename": "xa_hoa_luu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3223,
        "code": 31340,
        "name": "Phường Ngã Bảy",
        "codename": "phuong_nga_bay",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3224,
        "code": 31342,
        "name": "Xã Tân Hòa",
        "codename": "xa_tan_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3225,
        "code": 31348,
        "name": "Xã Trường Long Tây",
        "codename": "xa_truong_long_tay",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3226,
        "code": 31360,
        "name": "Xã Thạnh Xuân",
        "codename": "xa_thanh_xuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3227,
        "code": 31366,
        "name": "Xã Châu Thành",
        "codename": "xa_chau_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3228,
        "code": 31369,
        "name": "Xã Đông Phước",
        "codename": "xa_dong_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3229,
        "code": 31378,
        "name": "Xã Phú Hữu",
        "codename": "xa_phu_huu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3230,
        "code": 31393,
        "name": "Xã Hòa An",
        "codename": "xa_hoa_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3231,
        "code": 31396,
        "name": "Xã Hiệp Hưng",
        "codename": "xa_hiep_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3232,
        "code": 31399,
        "name": "Xã Tân Bình",
        "codename": "xa_tan_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3233,
        "code": 31408,
        "name": "Xã Thạnh Hòa",
        "codename": "xa_thanh_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3234,
        "code": 31411,
        "name": "Phường Đại Thành",
        "codename": "phuong_dai_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3235,
        "code": 31420,
        "name": "Xã Phụng Hiệp",
        "codename": "xa_phung_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3236,
        "code": 31426,
        "name": "Xã Phương Bình",
        "codename": "xa_phuong_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3237,
        "code": 31432,
        "name": "Xã Tân Phước Hưng",
        "codename": "xa_tan_phuoc_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3238,
        "code": 31441,
        "name": "Xã Vị Thủy",
        "codename": "xa_vi_thuy",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3239,
        "code": 31453,
        "name": "Xã Vĩnh Thuận Đông",
        "codename": "xa_vinh_thuan_dong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3240,
        "code": 31459,
        "name": "Xã Vĩnh Tường",
        "codename": "xa_vinh_tuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3241,
        "code": 31465,
        "name": "Xã Vị Thanh 1",
        "codename": "xa_vi_thanh_1",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3242,
        "code": 31471,
        "name": "Phường Long Mỹ",
        "codename": "phuong_long_my",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3243,
        "code": 31473,
        "name": "Phường Long Bình",
        "codename": "phuong_long_binh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3244,
        "code": 31480,
        "name": "Phường Long Phú 1",
        "codename": "phuong_long_phu_1",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3245,
        "code": 31489,
        "name": "Xã Vĩnh Viễn",
        "codename": "xa_vinh_vien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3246,
        "code": 31492,
        "name": "Xã Lương Tâm",
        "codename": "xa_luong_tam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3247,
        "code": 31495,
        "name": "Xã Xà Phiên",
        "codename": "xa_xa_phien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3248,
        "code": 31507,
        "name": "Phường Sóc Trăng",
        "codename": "phuong_soc_trang",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3249,
        "code": 31510,
        "name": "Phường Phú Lợi",
        "codename": "phuong_phu_loi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3250,
        "code": 31528,
        "name": "Xã Kế Sách",
        "codename": "xa_ke_sach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3251,
        "code": 31531,
        "name": "Xã An Lạc Thôn",
        "codename": "xa_an_lac_thon",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3252,
        "code": 31537,
        "name": "Xã Phong Nẫm",
        "codename": "xa_phong_nam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3253,
        "code": 31540,
        "name": "Xã Thới An Hội",
        "codename": "xa_thoi_an_hoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3254,
        "code": 31552,
        "name": "Xã Nhơn Mỹ",
        "codename": "xa_nhon_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3255,
        "code": 31561,
        "name": "Xã Đại Hải",
        "codename": "xa_dai_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3256,
        "code": 31567,
        "name": "Xã Mỹ Tú",
        "codename": "xa_my_tu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3257,
        "code": 31569,
        "name": "Xã Phú Tâm",
        "codename": "xa_phu_tam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3258,
        "code": 31570,
        "name": "Xã Hồ Đắc Kiện",
        "codename": "xa_ho_dac_kien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3259,
        "code": 31579,
        "name": "Xã Long Hưng",
        "codename": "xa_long_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3260,
        "code": 31582,
        "name": "Xã Thuận Hòa",
        "codename": "xa_thuan_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3261,
        "code": 31591,
        "name": "Xã Mỹ Hương",
        "codename": "xa_my_huong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3262,
        "code": 31594,
        "name": "Xã An Ninh",
        "codename": "xa_an_ninh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3263,
        "code": 31603,
        "name": "Xã Mỹ Phước",
        "codename": "xa_my_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3264,
        "code": 31615,
        "name": "Xã An Thạnh",
        "codename": "xa_an_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3265,
        "code": 31633,
        "name": "Xã Cù Lao Dung",
        "codename": "xa_cu_lao_dung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3266,
        "code": 31639,
        "name": "Xã Long Phú",
        "codename": "xa_long_phu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3267,
        "code": 31645,
        "name": "Xã Đại Ngãi",
        "codename": "xa_dai_ngai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3268,
        "code": 31654,
        "name": "Xã Trường Khánh",
        "codename": "xa_truong_khanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3269,
        "code": 31666,
        "name": "Xã Tân Thạnh",
        "codename": "xa_tan_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3270,
        "code": 31673,
        "name": "Xã Trần Đề",
        "codename": "xa_tran_de",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3271,
        "code": 31675,
        "name": "Xã Liêu Tú",
        "codename": "xa_lieu_tu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3272,
        "code": 31679,
        "name": "Xã Lịch Hội Thượng",
        "codename": "xa_lich_hoi_thuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3273,
        "code": 31684,
        "name": "Phường Mỹ Xuyên",
        "codename": "phuong_my_xuyen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3274,
        "code": 31687,
        "name": "Xã Tài Văn",
        "codename": "xa_tai_van",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3275,
        "code": 31699,
        "name": "Xã Thạnh Thới An",
        "codename": "xa_thanh_thoi_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3276,
        "code": 31708,
        "name": "Xã Nhu Gia",
        "codename": "xa_nhu_gia",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3277,
        "code": 31717,
        "name": "Xã Hòa Tú",
        "codename": "xa_hoa_tu",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3278,
        "code": 31723,
        "name": "Xã Ngọc Tố",
        "codename": "xa_ngoc_to",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3279,
        "code": 31726,
        "name": "Xã Gia Hòa",
        "codename": "xa_gia_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3280,
        "code": 31732,
        "name": "Phường Ngã Năm",
        "codename": "phuong_nga_nam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3281,
        "code": 31741,
        "name": "Xã Tân Long",
        "codename": "xa_tan_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3282,
        "code": 31753,
        "name": "Phường Mỹ Quới",
        "codename": "phuong_my_quoi",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3283,
        "code": 31756,
        "name": "Xã Phú Lộc",
        "codename": "xa_phu_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3284,
        "code": 31759,
        "name": "Xã Lâm Tân",
        "codename": "xa_lam_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3285,
        "code": 31777,
        "name": "Xã Vĩnh Lợi",
        "codename": "xa_vinh_loi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3286,
        "code": 31783,
        "name": "Phường Vĩnh Châu",
        "codename": "phuong_vinh_chau",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3287,
        "code": 31789,
        "name": "Phường Khánh Hòa",
        "codename": "phuong_khanh_hoa",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3288,
        "code": 31795,
        "name": "Xã Vĩnh Hải",
        "codename": "xa_vinh_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3289,
        "code": 31804,
        "name": "Phường Vĩnh Phước",
        "codename": "phuong_vinh_phuoc",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3290,
        "code": 31810,
        "name": "Xã Lai Hòa",
        "codename": "xa_lai_hoa",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 92
    },
    {
        "id": 3291,
        "code": 96,
        "name": "Tỉnh Cà Mau",
        "codename": "ca_mau",
        "division_type": "tỉnh",
        "phone_code": 290,
        "parent_code": null
    },
    {
        "id": 3292,
        "code": 31825,
        "name": "Phường Bạc Liêu",
        "codename": "phuong_bac_lieu",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3293,
        "code": 31834,
        "name": "Phường Vĩnh Trạch",
        "codename": "phuong_vinh_trach",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3294,
        "code": 31840,
        "name": "Phường Hiệp Thành",
        "codename": "phuong_hiep_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3295,
        "code": 31843,
        "name": "Xã Hồng Dân",
        "codename": "xa_hong_dan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3296,
        "code": 31849,
        "name": "Xã Ninh Quới",
        "codename": "xa_ninh_quoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3297,
        "code": 31858,
        "name": "Xã Vĩnh Lộc",
        "codename": "xa_vinh_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3298,
        "code": 31864,
        "name": "Xã Ninh Thạnh Lợi",
        "codename": "xa_ninh_thanh_loi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3299,
        "code": 31867,
        "name": "Xã Phước Long",
        "codename": "xa_phuoc_long",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3300,
        "code": 31876,
        "name": "Xã Vĩnh Phước",
        "codename": "xa_vinh_phuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3301,
        "code": 31882,
        "name": "Xã Vĩnh Thanh",
        "codename": "xa_vinh_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3302,
        "code": 31885,
        "name": "Xã Phong Hiệp",
        "codename": "xa_phong_hiep",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3303,
        "code": 31891,
        "name": "Xã Hòa Bình",
        "codename": "xa_hoa_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3304,
        "code": 31894,
        "name": "Xã Châu Thới",
        "codename": "xa_chau_thoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3305,
        "code": 31900,
        "name": "Xã Vĩnh Lợi",
        "codename": "xa_vinh_loi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3306,
        "code": 31906,
        "name": "Xã Hưng Hội",
        "codename": "xa_hung_hoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3307,
        "code": 31918,
        "name": "Xã Vĩnh Mỹ",
        "codename": "xa_vinh_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3308,
        "code": 31927,
        "name": "Xã Vĩnh Hậu",
        "codename": "xa_vinh_hau",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3309,
        "code": 31942,
        "name": "Phường Giá Rai",
        "codename": "phuong_gia_rai",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3310,
        "code": 31951,
        "name": "Phường Láng Tròn",
        "codename": "phuong_lang_tron",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3311,
        "code": 31957,
        "name": "Xã Phong Thạnh",
        "codename": "xa_phong_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3312,
        "code": 31972,
        "name": "Xã Gành Hào",
        "codename": "xa_ganh_hao",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3313,
        "code": 31975,
        "name": "Xã Đông Hải",
        "codename": "xa_dong_hai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3314,
        "code": 31985,
        "name": "Xã Long Điền",
        "codename": "xa_long_dien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3315,
        "code": 31988,
        "name": "Xã An Trạch",
        "codename": "xa_an_trach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3316,
        "code": 31993,
        "name": "Xã Định Thành",
        "codename": "xa_dinh_thanh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3317,
        "code": 32002,
        "name": "Phường An Xuyên",
        "codename": "phuong_an_xuyen",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3318,
        "code": 32014,
        "name": "Phường Lý Văn Lâm",
        "codename": "phuong_ly_van_lam",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3319,
        "code": 32025,
        "name": "Phường Tân Thành",
        "codename": "phuong_tan_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3320,
        "code": 32041,
        "name": "Phường Hòa Thành",
        "codename": "phuong_hoa_thanh",
        "division_type": "phường",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3321,
        "code": 32044,
        "name": "Xã Nguyễn Phích",
        "codename": "xa_nguyen_phich",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3322,
        "code": 32047,
        "name": "Xã U Minh",
        "codename": "xa_u_minh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3323,
        "code": 32059,
        "name": "Xã Khánh An",
        "codename": "xa_khanh_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3324,
        "code": 32062,
        "name": "Xã Khánh Lâm",
        "codename": "xa_khanh_lam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3325,
        "code": 32065,
        "name": "Xã Thới Bình",
        "codename": "xa_thoi_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3326,
        "code": 32069,
        "name": "Xã Biển Bạch",
        "codename": "xa_bien_bach",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3327,
        "code": 32071,
        "name": "Xã Trí Phải",
        "codename": "xa_tri_phai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3328,
        "code": 32083,
        "name": "Xã Tân Lộc",
        "codename": "xa_tan_loc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3329,
        "code": 32092,
        "name": "Xã Hồ Thị Kỷ",
        "codename": "xa_ho_thi_ky",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3330,
        "code": 32095,
        "name": "Xã Trần Văn Thời",
        "codename": "xa_tran_van_thoi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3331,
        "code": 32098,
        "name": "Xã Sông Đốc",
        "codename": "xa_song_doc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3332,
        "code": 32104,
        "name": "Xã Đá Bạc",
        "codename": "xa_da_bac",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3333,
        "code": 32110,
        "name": "Xã Khánh Bình",
        "codename": "xa_khanh_binh",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3334,
        "code": 32119,
        "name": "Xã Khánh Hưng",
        "codename": "xa_khanh_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3335,
        "code": 32128,
        "name": "Xã Cái Nước",
        "codename": "xa_cai_nuoc",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3336,
        "code": 32134,
        "name": "Xã Lương Thế Trân",
        "codename": "xa_luong_the_tran",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3337,
        "code": 32137,
        "name": "Xã Tân Hưng",
        "codename": "xa_tan_hung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3338,
        "code": 32140,
        "name": "Xã Hưng Mỹ",
        "codename": "xa_hung_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3339,
        "code": 32152,
        "name": "Xã Đầm Dơi",
        "codename": "xa_dam_doi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3340,
        "code": 32155,
        "name": "Xã Tạ An Khương",
        "codename": "xa_ta_an_khuong",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3341,
        "code": 32161,
        "name": "Xã Trần Phán",
        "codename": "xa_tran_phan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3342,
        "code": 32167,
        "name": "Xã Tân Thuận",
        "codename": "xa_tan_thuan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3343,
        "code": 32182,
        "name": "Xã Quách Phẩm",
        "codename": "xa_quach_pham",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3344,
        "code": 32185,
        "name": "Xã Thanh Tùng",
        "codename": "xa_thanh_tung",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3345,
        "code": 32188,
        "name": "Xã Tân Tiến",
        "codename": "xa_tan_tien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3346,
        "code": 32191,
        "name": "Xã Năm Căn",
        "codename": "xa_nam_can",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3347,
        "code": 32201,
        "name": "Xã Đất Mới",
        "codename": "xa_dat_moi",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3348,
        "code": 32206,
        "name": "Xã Tam Giang",
        "codename": "xa_tam_giang",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3349,
        "code": 32212,
        "name": "Xã Cái Đôi Vàm",
        "codename": "xa_cai_doi_vam",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3350,
        "code": 32214,
        "name": "Xã Phú Mỹ",
        "codename": "xa_phu_my",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3351,
        "code": 32218,
        "name": "Xã Phú Tân",
        "codename": "xa_phu_tan",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3352,
        "code": 32227,
        "name": "Xã Nguyễn Việt Khái",
        "codename": "xa_nguyen_viet_khai",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3353,
        "code": 32236,
        "name": "Xã Tân Ân",
        "codename": "xa_tan_an",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3354,
        "code": 32244,
        "name": "Xã Phan Ngọc Hiển",
        "codename": "xa_phan_ngoc_hien",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    },
    {
        "id": 3355,
        "code": 32248,
        "name": "Xã Đất Mũi",
        "codename": "xa_dat_mui",
        "division_type": "xã",
        "phone_code": null,
        "parent_code": 96
    }
]
JSON, true, 512, JSON_THROW_ON_ERROR);
    }
};
