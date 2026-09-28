<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class RoleHasPermissionsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('role_has_permissions')->delete();
        
        \DB::table('role_has_permissions')->insert(array (
            0 => 
            array (
                'permission_id' => 42,
                'role_id' => 3,
            ),
            1 => 
            array (
                'permission_id' => 43,
                'role_id' => 3,
            ),
            2 => 
            array (
                'permission_id' => 44,
                'role_id' => 3,
            ),
            3 => 
            array (
                'permission_id' => 45,
                'role_id' => 3,
            ),
            4 => 
            array (
                'permission_id' => 74,
                'role_id' => 3,
            ),
            5 => 
            array (
                'permission_id' => 74,
                'role_id' => 4,
            ),
            6 => 
            array (
                'permission_id' => 241,
                'role_id' => 4,
            ),
            7 => 
            array (
                'permission_id' => 42,
                'role_id' => 6,
            ),
            8 => 
            array (
                'permission_id' => 43,
                'role_id' => 6,
            ),
            9 => 
            array (
                'permission_id' => 44,
                'role_id' => 6,
            ),
            10 => 
            array (
                'permission_id' => 45,
                'role_id' => 6,
            ),
            11 => 
            array (
                'permission_id' => 74,
                'role_id' => 6,
            ),
            12 => 
            array (
                'permission_id' => 74,
                'role_id' => 7,
            ),
            13 => 
            array (
                'permission_id' => 241,
                'role_id' => 7,
            ),
            14 => 
            array (
                'permission_id' => 42,
                'role_id' => 9,
            ),
            15 => 
            array (
                'permission_id' => 43,
                'role_id' => 9,
            ),
            16 => 
            array (
                'permission_id' => 44,
                'role_id' => 9,
            ),
            17 => 
            array (
                'permission_id' => 45,
                'role_id' => 9,
            ),
            18 => 
            array (
                'permission_id' => 74,
                'role_id' => 9,
            ),
            19 => 
            array (
                'permission_id' => 74,
                'role_id' => 10,
            ),
            20 => 
            array (
                'permission_id' => 241,
                'role_id' => 10,
            ),
            21 => 
            array (
                'permission_id' => 1,
                'role_id' => 11,
            ),
            22 => 
            array (
                'permission_id' => 3,
                'role_id' => 11,
            ),
            23 => 
            array (
                'permission_id' => 6,
                'role_id' => 11,
            ),
            24 => 
            array (
                'permission_id' => 15,
                'role_id' => 11,
            ),
            25 => 
            array (
                'permission_id' => 17,
                'role_id' => 11,
            ),
            26 => 
            array (
                'permission_id' => 26,
                'role_id' => 11,
            ),
            27 => 
            array (
                'permission_id' => 27,
                'role_id' => 11,
            ),
            28 => 
            array (
                'permission_id' => 30,
                'role_id' => 11,
            ),
            29 => 
            array (
                'permission_id' => 31,
                'role_id' => 11,
            ),
            30 => 
            array (
                'permission_id' => 34,
                'role_id' => 11,
            ),
            31 => 
            array (
                'permission_id' => 35,
                'role_id' => 11,
            ),
            32 => 
            array (
                'permission_id' => 36,
                'role_id' => 11,
            ),
            33 => 
            array (
                'permission_id' => 38,
                'role_id' => 11,
            ),
            34 => 
            array (
                'permission_id' => 39,
                'role_id' => 11,
            ),
            35 => 
            array (
                'permission_id' => 40,
                'role_id' => 11,
            ),
            36 => 
            array (
                'permission_id' => 48,
                'role_id' => 11,
            ),
            37 => 
            array (
                'permission_id' => 49,
                'role_id' => 11,
            ),
            38 => 
            array (
                'permission_id' => 51,
                'role_id' => 11,
            ),
            39 => 
            array (
                'permission_id' => 52,
                'role_id' => 11,
            ),
            40 => 
            array (
                'permission_id' => 53,
                'role_id' => 11,
            ),
            41 => 
            array (
                'permission_id' => 55,
                'role_id' => 11,
            ),
            42 => 
            array (
                'permission_id' => 75,
                'role_id' => 11,
            ),
            43 => 
            array (
                'permission_id' => 81,
                'role_id' => 11,
            ),
            44 => 
            array (
                'permission_id' => 125,
                'role_id' => 11,
            ),
            45 => 
            array (
                'permission_id' => 130,
                'role_id' => 11,
            ),
            46 => 
            array (
                'permission_id' => 137,
                'role_id' => 11,
            ),
            47 => 
            array (
                'permission_id' => 140,
                'role_id' => 11,
            ),
            48 => 
            array (
                'permission_id' => 141,
                'role_id' => 11,
            ),
            49 => 
            array (
                'permission_id' => 142,
                'role_id' => 11,
            ),
            50 => 
            array (
                'permission_id' => 166,
                'role_id' => 11,
            ),
            51 => 
            array (
                'permission_id' => 169,
                'role_id' => 11,
            ),
            52 => 
            array (
                'permission_id' => 172,
                'role_id' => 11,
            ),
            53 => 
            array (
                'permission_id' => 173,
                'role_id' => 11,
            ),
            54 => 
            array (
                'permission_id' => 174,
                'role_id' => 11,
            ),
            55 => 
            array (
                'permission_id' => 175,
                'role_id' => 11,
            ),
            56 => 
            array (
                'permission_id' => 180,
                'role_id' => 11,
            ),
            57 => 
            array (
                'permission_id' => 202,
                'role_id' => 11,
            ),
            58 => 
            array (
                'permission_id' => 203,
                'role_id' => 11,
            ),
            59 => 
            array (
                'permission_id' => 204,
                'role_id' => 11,
            ),
            60 => 
            array (
                'permission_id' => 205,
                'role_id' => 11,
            ),
            61 => 
            array (
                'permission_id' => 206,
                'role_id' => 11,
            ),
            62 => 
            array (
                'permission_id' => 207,
                'role_id' => 11,
            ),
            63 => 
            array (
                'permission_id' => 208,
                'role_id' => 11,
            ),
            64 => 
            array (
                'permission_id' => 209,
                'role_id' => 11,
            ),
            65 => 
            array (
                'permission_id' => 210,
                'role_id' => 11,
            ),
            66 => 
            array (
                'permission_id' => 211,
                'role_id' => 11,
            ),
            67 => 
            array (
                'permission_id' => 212,
                'role_id' => 11,
            ),
            68 => 
            array (
                'permission_id' => 214,
                'role_id' => 11,
            ),
            69 => 
            array (
                'permission_id' => 215,
                'role_id' => 11,
            ),
            70 => 
            array (
                'permission_id' => 216,
                'role_id' => 11,
            ),
            71 => 
            array (
                'permission_id' => 217,
                'role_id' => 11,
            ),
            72 => 
            array (
                'permission_id' => 218,
                'role_id' => 11,
            ),
            73 => 
            array (
                'permission_id' => 219,
                'role_id' => 11,
            ),
            74 => 
            array (
                'permission_id' => 230,
                'role_id' => 11,
            ),
            75 => 
            array (
                'permission_id' => 236,
                'role_id' => 11,
            ),
            76 => 
            array (
                'permission_id' => 240,
                'role_id' => 11,
            ),
            77 => 
            array (
                'permission_id' => 241,
                'role_id' => 11,
            ),
            78 => 
            array (
                'permission_id' => 242,
                'role_id' => 11,
            ),
            79 => 
            array (
                'permission_id' => 245,
                'role_id' => 11,
            ),
            80 => 
            array (
                'permission_id' => 308,
                'role_id' => 11,
            ),
            81 => 
            array (
                'permission_id' => 316,
                'role_id' => 11,
            ),
            82 => 
            array (
                'permission_id' => 331,
                'role_id' => 11,
            ),
            83 => 
            array (
                'permission_id' => 339,
                'role_id' => 11,
            ),
            84 => 
            array (
                'permission_id' => 368,
                'role_id' => 11,
            ),
            85 => 
            array (
                'permission_id' => 391,
                'role_id' => 11,
            ),
            86 => 
            array (
                'permission_id' => 392,
                'role_id' => 11,
            ),
            87 => 
            array (
                'permission_id' => 393,
                'role_id' => 11,
            ),
            88 => 
            array (
                'permission_id' => 395,
                'role_id' => 11,
            ),
            89 => 
            array (
                'permission_id' => 398,
                'role_id' => 11,
            ),
            90 => 
            array (
                'permission_id' => 399,
                'role_id' => 11,
            ),
            91 => 
            array (
                'permission_id' => 400,
                'role_id' => 11,
            ),
            92 => 
            array (
                'permission_id' => 401,
                'role_id' => 11,
            ),
            93 => 
            array (
                'permission_id' => 402,
                'role_id' => 11,
            ),
            94 => 
            array (
                'permission_id' => 403,
                'role_id' => 11,
            ),
            95 => 
            array (
                'permission_id' => 411,
                'role_id' => 11,
            ),
            96 => 
            array (
                'permission_id' => 450,
                'role_id' => 11,
            ),
            97 => 
            array (
                'permission_id' => 451,
                'role_id' => 11,
            ),
            98 => 
            array (
                'permission_id' => 452,
                'role_id' => 11,
            ),
            99 => 
            array (
                'permission_id' => 453,
                'role_id' => 11,
            ),
            100 => 
            array (
                'permission_id' => 454,
                'role_id' => 11,
            ),
            101 => 
            array (
                'permission_id' => 455,
                'role_id' => 11,
            ),
            102 => 
            array (
                'permission_id' => 456,
                'role_id' => 11,
            ),
            103 => 
            array (
                'permission_id' => 472,
                'role_id' => 11,
            ),
            104 => 
            array (
                'permission_id' => 473,
                'role_id' => 11,
            ),
            105 => 
            array (
                'permission_id' => 474,
                'role_id' => 11,
            ),
            106 => 
            array (
                'permission_id' => 475,
                'role_id' => 11,
            ),
            107 => 
            array (
                'permission_id' => 476,
                'role_id' => 11,
            ),
            108 => 
            array (
                'permission_id' => 498,
                'role_id' => 11,
            ),
            109 => 
            array (
                'permission_id' => 500,
                'role_id' => 11,
            ),
            110 => 
            array (
                'permission_id' => 501,
                'role_id' => 11,
            ),
            111 => 
            array (
                'permission_id' => 512,
                'role_id' => 11,
            ),
            112 => 
            array (
                'permission_id' => 513,
                'role_id' => 11,
            ),
            113 => 
            array (
                'permission_id' => 514,
                'role_id' => 11,
            ),
            114 => 
            array (
                'permission_id' => 515,
                'role_id' => 11,
            ),
            115 => 
            array (
                'permission_id' => 1,
                'role_id' => 12,
            ),
            116 => 
            array (
                'permission_id' => 2,
                'role_id' => 12,
            ),
            117 => 
            array (
                'permission_id' => 3,
                'role_id' => 12,
            ),
            118 => 
            array (
                'permission_id' => 15,
                'role_id' => 12,
            ),
            119 => 
            array (
                'permission_id' => 17,
                'role_id' => 12,
            ),
            120 => 
            array (
                'permission_id' => 18,
                'role_id' => 12,
            ),
            121 => 
            array (
                'permission_id' => 26,
                'role_id' => 12,
            ),
            122 => 
            array (
                'permission_id' => 27,
                'role_id' => 12,
            ),
            123 => 
            array (
                'permission_id' => 30,
                'role_id' => 12,
            ),
            124 => 
            array (
                'permission_id' => 31,
                'role_id' => 12,
            ),
            125 => 
            array (
                'permission_id' => 34,
                'role_id' => 12,
            ),
            126 => 
            array (
                'permission_id' => 35,
                'role_id' => 12,
            ),
            127 => 
            array (
                'permission_id' => 36,
                'role_id' => 12,
            ),
            128 => 
            array (
                'permission_id' => 38,
                'role_id' => 12,
            ),
            129 => 
            array (
                'permission_id' => 39,
                'role_id' => 12,
            ),
            130 => 
            array (
                'permission_id' => 40,
                'role_id' => 12,
            ),
            131 => 
            array (
                'permission_id' => 42,
                'role_id' => 12,
            ),
            132 => 
            array (
                'permission_id' => 48,
                'role_id' => 12,
            ),
            133 => 
            array (
                'permission_id' => 49,
                'role_id' => 12,
            ),
            134 => 
            array (
                'permission_id' => 51,
                'role_id' => 12,
            ),
            135 => 
            array (
                'permission_id' => 52,
                'role_id' => 12,
            ),
            136 => 
            array (
                'permission_id' => 53,
                'role_id' => 12,
            ),
            137 => 
            array (
                'permission_id' => 73,
                'role_id' => 12,
            ),
            138 => 
            array (
                'permission_id' => 75,
                'role_id' => 12,
            ),
            139 => 
            array (
                'permission_id' => 81,
                'role_id' => 12,
            ),
            140 => 
            array (
                'permission_id' => 83,
                'role_id' => 12,
            ),
            141 => 
            array (
                'permission_id' => 125,
                'role_id' => 12,
            ),
            142 => 
            array (
                'permission_id' => 129,
                'role_id' => 12,
            ),
            143 => 
            array (
                'permission_id' => 130,
                'role_id' => 12,
            ),
            144 => 
            array (
                'permission_id' => 137,
                'role_id' => 12,
            ),
            145 => 
            array (
                'permission_id' => 140,
                'role_id' => 12,
            ),
            146 => 
            array (
                'permission_id' => 141,
                'role_id' => 12,
            ),
            147 => 
            array (
                'permission_id' => 142,
                'role_id' => 12,
            ),
            148 => 
            array (
                'permission_id' => 166,
                'role_id' => 12,
            ),
            149 => 
            array (
                'permission_id' => 169,
                'role_id' => 12,
            ),
            150 => 
            array (
                'permission_id' => 172,
                'role_id' => 12,
            ),
            151 => 
            array (
                'permission_id' => 173,
                'role_id' => 12,
            ),
            152 => 
            array (
                'permission_id' => 174,
                'role_id' => 12,
            ),
            153 => 
            array (
                'permission_id' => 175,
                'role_id' => 12,
            ),
            154 => 
            array (
                'permission_id' => 180,
                'role_id' => 12,
            ),
            155 => 
            array (
                'permission_id' => 202,
                'role_id' => 12,
            ),
            156 => 
            array (
                'permission_id' => 203,
                'role_id' => 12,
            ),
            157 => 
            array (
                'permission_id' => 204,
                'role_id' => 12,
            ),
            158 => 
            array (
                'permission_id' => 205,
                'role_id' => 12,
            ),
            159 => 
            array (
                'permission_id' => 206,
                'role_id' => 12,
            ),
            160 => 
            array (
                'permission_id' => 207,
                'role_id' => 12,
            ),
            161 => 
            array (
                'permission_id' => 208,
                'role_id' => 12,
            ),
            162 => 
            array (
                'permission_id' => 209,
                'role_id' => 12,
            ),
            163 => 
            array (
                'permission_id' => 210,
                'role_id' => 12,
            ),
            164 => 
            array (
                'permission_id' => 211,
                'role_id' => 12,
            ),
            165 => 
            array (
                'permission_id' => 212,
                'role_id' => 12,
            ),
            166 => 
            array (
                'permission_id' => 214,
                'role_id' => 12,
            ),
            167 => 
            array (
                'permission_id' => 215,
                'role_id' => 12,
            ),
            168 => 
            array (
                'permission_id' => 216,
                'role_id' => 12,
            ),
            169 => 
            array (
                'permission_id' => 217,
                'role_id' => 12,
            ),
            170 => 
            array (
                'permission_id' => 218,
                'role_id' => 12,
            ),
            171 => 
            array (
                'permission_id' => 219,
                'role_id' => 12,
            ),
            172 => 
            array (
                'permission_id' => 230,
                'role_id' => 12,
            ),
            173 => 
            array (
                'permission_id' => 231,
                'role_id' => 12,
            ),
            174 => 
            array (
                'permission_id' => 236,
                'role_id' => 12,
            ),
            175 => 
            array (
                'permission_id' => 240,
                'role_id' => 12,
            ),
            176 => 
            array (
                'permission_id' => 241,
                'role_id' => 12,
            ),
            177 => 
            array (
                'permission_id' => 242,
                'role_id' => 12,
            ),
            178 => 
            array (
                'permission_id' => 245,
                'role_id' => 12,
            ),
            179 => 
            array (
                'permission_id' => 281,
                'role_id' => 12,
            ),
            180 => 
            array (
                'permission_id' => 308,
                'role_id' => 12,
            ),
            181 => 
            array (
                'permission_id' => 316,
                'role_id' => 12,
            ),
            182 => 
            array (
                'permission_id' => 331,
                'role_id' => 12,
            ),
            183 => 
            array (
                'permission_id' => 339,
                'role_id' => 12,
            ),
            184 => 
            array (
                'permission_id' => 368,
                'role_id' => 12,
            ),
            185 => 
            array (
                'permission_id' => 391,
                'role_id' => 12,
            ),
            186 => 
            array (
                'permission_id' => 392,
                'role_id' => 12,
            ),
            187 => 
            array (
                'permission_id' => 393,
                'role_id' => 12,
            ),
            188 => 
            array (
                'permission_id' => 395,
                'role_id' => 12,
            ),
            189 => 
            array (
                'permission_id' => 398,
                'role_id' => 12,
            ),
            190 => 
            array (
                'permission_id' => 399,
                'role_id' => 12,
            ),
            191 => 
            array (
                'permission_id' => 400,
                'role_id' => 12,
            ),
            192 => 
            array (
                'permission_id' => 401,
                'role_id' => 12,
            ),
            193 => 
            array (
                'permission_id' => 402,
                'role_id' => 12,
            ),
            194 => 
            array (
                'permission_id' => 403,
                'role_id' => 12,
            ),
            195 => 
            array (
                'permission_id' => 409,
                'role_id' => 12,
            ),
            196 => 
            array (
                'permission_id' => 411,
                'role_id' => 12,
            ),
            197 => 
            array (
                'permission_id' => 450,
                'role_id' => 12,
            ),
            198 => 
            array (
                'permission_id' => 451,
                'role_id' => 12,
            ),
            199 => 
            array (
                'permission_id' => 452,
                'role_id' => 12,
            ),
            200 => 
            array (
                'permission_id' => 453,
                'role_id' => 12,
            ),
            201 => 
            array (
                'permission_id' => 454,
                'role_id' => 12,
            ),
            202 => 
            array (
                'permission_id' => 455,
                'role_id' => 12,
            ),
            203 => 
            array (
                'permission_id' => 456,
                'role_id' => 12,
            ),
            204 => 
            array (
                'permission_id' => 472,
                'role_id' => 12,
            ),
            205 => 
            array (
                'permission_id' => 473,
                'role_id' => 12,
            ),
            206 => 
            array (
                'permission_id' => 474,
                'role_id' => 12,
            ),
            207 => 
            array (
                'permission_id' => 475,
                'role_id' => 12,
            ),
            208 => 
            array (
                'permission_id' => 476,
                'role_id' => 12,
            ),
            209 => 
            array (
                'permission_id' => 498,
                'role_id' => 12,
            ),
            210 => 
            array (
                'permission_id' => 500,
                'role_id' => 12,
            ),
            211 => 
            array (
                'permission_id' => 501,
                'role_id' => 12,
            ),
            212 => 
            array (
                'permission_id' => 502,
                'role_id' => 12,
            ),
            213 => 
            array (
                'permission_id' => 503,
                'role_id' => 12,
            ),
            214 => 
            array (
                'permission_id' => 504,
                'role_id' => 12,
            ),
            215 => 
            array (
                'permission_id' => 505,
                'role_id' => 12,
            ),
            216 => 
            array (
                'permission_id' => 506,
                'role_id' => 12,
            ),
            217 => 
            array (
                'permission_id' => 507,
                'role_id' => 12,
            ),
            218 => 
            array (
                'permission_id' => 508,
                'role_id' => 12,
            ),
            219 => 
            array (
                'permission_id' => 509,
                'role_id' => 12,
            ),
            220 => 
            array (
                'permission_id' => 512,
                'role_id' => 12,
            ),
            221 => 
            array (
                'permission_id' => 513,
                'role_id' => 12,
            ),
            222 => 
            array (
                'permission_id' => 514,
                'role_id' => 12,
            ),
            223 => 
            array (
                'permission_id' => 515,
                'role_id' => 12,
            ),
            224 => 
            array (
                'permission_id' => 516,
                'role_id' => 12,
            ),
            225 => 
            array (
                'permission_id' => 517,
                'role_id' => 12,
            ),
            226 => 
            array (
                'permission_id' => 1,
                'role_id' => 13,
            ),
            227 => 
            array (
                'permission_id' => 3,
                'role_id' => 13,
            ),
            228 => 
            array (
                'permission_id' => 6,
                'role_id' => 13,
            ),
            229 => 
            array (
                'permission_id' => 15,
                'role_id' => 13,
            ),
            230 => 
            array (
                'permission_id' => 17,
                'role_id' => 13,
            ),
            231 => 
            array (
                'permission_id' => 26,
                'role_id' => 13,
            ),
            232 => 
            array (
                'permission_id' => 27,
                'role_id' => 13,
            ),
            233 => 
            array (
                'permission_id' => 28,
                'role_id' => 13,
            ),
            234 => 
            array (
                'permission_id' => 30,
                'role_id' => 13,
            ),
            235 => 
            array (
                'permission_id' => 31,
                'role_id' => 13,
            ),
            236 => 
            array (
                'permission_id' => 32,
                'role_id' => 13,
            ),
            237 => 
            array (
                'permission_id' => 34,
                'role_id' => 13,
            ),
            238 => 
            array (
                'permission_id' => 35,
                'role_id' => 13,
            ),
            239 => 
            array (
                'permission_id' => 36,
                'role_id' => 13,
            ),
            240 => 
            array (
                'permission_id' => 37,
                'role_id' => 13,
            ),
            241 => 
            array (
                'permission_id' => 38,
                'role_id' => 13,
            ),
            242 => 
            array (
                'permission_id' => 39,
                'role_id' => 13,
            ),
            243 => 
            array (
                'permission_id' => 48,
                'role_id' => 13,
            ),
            244 => 
            array (
                'permission_id' => 49,
                'role_id' => 13,
            ),
            245 => 
            array (
                'permission_id' => 51,
                'role_id' => 13,
            ),
            246 => 
            array (
                'permission_id' => 52,
                'role_id' => 13,
            ),
            247 => 
            array (
                'permission_id' => 53,
                'role_id' => 13,
            ),
            248 => 
            array (
                'permission_id' => 54,
                'role_id' => 13,
            ),
            249 => 
            array (
                'permission_id' => 56,
                'role_id' => 13,
            ),
            250 => 
            array (
                'permission_id' => 73,
                'role_id' => 13,
            ),
            251 => 
            array (
                'permission_id' => 75,
                'role_id' => 13,
            ),
            252 => 
            array (
                'permission_id' => 81,
                'role_id' => 13,
            ),
            253 => 
            array (
                'permission_id' => 125,
                'role_id' => 13,
            ),
            254 => 
            array (
                'permission_id' => 130,
                'role_id' => 13,
            ),
            255 => 
            array (
                'permission_id' => 137,
                'role_id' => 13,
            ),
            256 => 
            array (
                'permission_id' => 140,
                'role_id' => 13,
            ),
            257 => 
            array (
                'permission_id' => 141,
                'role_id' => 13,
            ),
            258 => 
            array (
                'permission_id' => 142,
                'role_id' => 13,
            ),
            259 => 
            array (
                'permission_id' => 166,
                'role_id' => 13,
            ),
            260 => 
            array (
                'permission_id' => 169,
                'role_id' => 13,
            ),
            261 => 
            array (
                'permission_id' => 172,
                'role_id' => 13,
            ),
            262 => 
            array (
                'permission_id' => 173,
                'role_id' => 13,
            ),
            263 => 
            array (
                'permission_id' => 174,
                'role_id' => 13,
            ),
            264 => 
            array (
                'permission_id' => 175,
                'role_id' => 13,
            ),
            265 => 
            array (
                'permission_id' => 180,
                'role_id' => 13,
            ),
            266 => 
            array (
                'permission_id' => 202,
                'role_id' => 13,
            ),
            267 => 
            array (
                'permission_id' => 203,
                'role_id' => 13,
            ),
            268 => 
            array (
                'permission_id' => 204,
                'role_id' => 13,
            ),
            269 => 
            array (
                'permission_id' => 205,
                'role_id' => 13,
            ),
            270 => 
            array (
                'permission_id' => 206,
                'role_id' => 13,
            ),
            271 => 
            array (
                'permission_id' => 207,
                'role_id' => 13,
            ),
            272 => 
            array (
                'permission_id' => 208,
                'role_id' => 13,
            ),
            273 => 
            array (
                'permission_id' => 209,
                'role_id' => 13,
            ),
            274 => 
            array (
                'permission_id' => 210,
                'role_id' => 13,
            ),
            275 => 
            array (
                'permission_id' => 211,
                'role_id' => 13,
            ),
            276 => 
            array (
                'permission_id' => 212,
                'role_id' => 13,
            ),
            277 => 
            array (
                'permission_id' => 214,
                'role_id' => 13,
            ),
            278 => 
            array (
                'permission_id' => 215,
                'role_id' => 13,
            ),
            279 => 
            array (
                'permission_id' => 216,
                'role_id' => 13,
            ),
            280 => 
            array (
                'permission_id' => 217,
                'role_id' => 13,
            ),
            281 => 
            array (
                'permission_id' => 218,
                'role_id' => 13,
            ),
            282 => 
            array (
                'permission_id' => 219,
                'role_id' => 13,
            ),
            283 => 
            array (
                'permission_id' => 230,
                'role_id' => 13,
            ),
            284 => 
            array (
                'permission_id' => 231,
                'role_id' => 13,
            ),
            285 => 
            array (
                'permission_id' => 236,
                'role_id' => 13,
            ),
            286 => 
            array (
                'permission_id' => 240,
                'role_id' => 13,
            ),
            287 => 
            array (
                'permission_id' => 241,
                'role_id' => 13,
            ),
            288 => 
            array (
                'permission_id' => 242,
                'role_id' => 13,
            ),
            289 => 
            array (
                'permission_id' => 245,
                'role_id' => 13,
            ),
            290 => 
            array (
                'permission_id' => 281,
                'role_id' => 13,
            ),
            291 => 
            array (
                'permission_id' => 282,
                'role_id' => 13,
            ),
            292 => 
            array (
                'permission_id' => 283,
                'role_id' => 13,
            ),
            293 => 
            array (
                'permission_id' => 308,
                'role_id' => 13,
            ),
            294 => 
            array (
                'permission_id' => 316,
                'role_id' => 13,
            ),
            295 => 
            array (
                'permission_id' => 331,
                'role_id' => 13,
            ),
            296 => 
            array (
                'permission_id' => 339,
                'role_id' => 13,
            ),
            297 => 
            array (
                'permission_id' => 368,
                'role_id' => 13,
            ),
            298 => 
            array (
                'permission_id' => 391,
                'role_id' => 13,
            ),
            299 => 
            array (
                'permission_id' => 392,
                'role_id' => 13,
            ),
            300 => 
            array (
                'permission_id' => 393,
                'role_id' => 13,
            ),
            301 => 
            array (
                'permission_id' => 395,
                'role_id' => 13,
            ),
            302 => 
            array (
                'permission_id' => 398,
                'role_id' => 13,
            ),
            303 => 
            array (
                'permission_id' => 399,
                'role_id' => 13,
            ),
            304 => 
            array (
                'permission_id' => 400,
                'role_id' => 13,
            ),
            305 => 
            array (
                'permission_id' => 401,
                'role_id' => 13,
            ),
            306 => 
            array (
                'permission_id' => 402,
                'role_id' => 13,
            ),
            307 => 
            array (
                'permission_id' => 403,
                'role_id' => 13,
            ),
            308 => 
            array (
                'permission_id' => 411,
                'role_id' => 13,
            ),
            309 => 
            array (
                'permission_id' => 450,
                'role_id' => 13,
            ),
            310 => 
            array (
                'permission_id' => 451,
                'role_id' => 13,
            ),
            311 => 
            array (
                'permission_id' => 452,
                'role_id' => 13,
            ),
            312 => 
            array (
                'permission_id' => 453,
                'role_id' => 13,
            ),
            313 => 
            array (
                'permission_id' => 454,
                'role_id' => 13,
            ),
            314 => 
            array (
                'permission_id' => 455,
                'role_id' => 13,
            ),
            315 => 
            array (
                'permission_id' => 456,
                'role_id' => 13,
            ),
            316 => 
            array (
                'permission_id' => 472,
                'role_id' => 13,
            ),
            317 => 
            array (
                'permission_id' => 473,
                'role_id' => 13,
            ),
            318 => 
            array (
                'permission_id' => 474,
                'role_id' => 13,
            ),
            319 => 
            array (
                'permission_id' => 475,
                'role_id' => 13,
            ),
            320 => 
            array (
                'permission_id' => 476,
                'role_id' => 13,
            ),
            321 => 
            array (
                'permission_id' => 498,
                'role_id' => 13,
            ),
            322 => 
            array (
                'permission_id' => 500,
                'role_id' => 13,
            ),
            323 => 
            array (
                'permission_id' => 501,
                'role_id' => 13,
            ),
            324 => 
            array (
                'permission_id' => 512,
                'role_id' => 13,
            ),
            325 => 
            array (
                'permission_id' => 513,
                'role_id' => 13,
            ),
            326 => 
            array (
                'permission_id' => 514,
                'role_id' => 13,
            ),
            327 => 
            array (
                'permission_id' => 515,
                'role_id' => 13,
            ),
            328 => 
            array (
                'permission_id' => 516,
                'role_id' => 13,
            ),
            329 => 
            array (
                'permission_id' => 517,
                'role_id' => 13,
            ),
        ));
        
        
    }
}