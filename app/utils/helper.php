<?php

use App\Repositories\MenuRepository;
use Illuminate\Support\Facades\Cache;

if (!function_exists('menus')) {
    /**
     * @return Collection
     */
    function menus()
    {
        if (!Cache::has('menus')) {
            $menus = (new MenuRepository())->getMenus()->groupBy('category');

            Cache::forever('menus', $menus);
        } else {
            $menus = Cache::get('menus');
        }

        return $menus;
    }

    if (!function_exists('user')) {
        /**
         * @param string $id
         * @return \App\Models\User | String
         */
        function user($id = null)
        {
            if ($id) {
                return request()->user()->{$id};
            }
            return request()->user();
        }
    }

    if (!function_exists('responseSuccess')) {
        function responseSuccess($isEdit = false, $customMessage = false)
        {

            if($customMessage){
                return response()->json([
                    'status' => 'success',
                    'message' => $customMessage
                ]);
            }else{
                return response()->json([
                    'status' => 'success',
                    'message' => $isEdit ? 'Update data Successfully' : 'Create data Successfully',
                ]);
            }

        }
    }

    if (!function_exists('responseWarning')) {
        function responseWarning($message)
        {
            return response()->json([
                'status' => 'warning',
                'message' => $message
            ]);
        }
    }

    if (!function_exists('urlMenu')) {
        function urlMenu()
        {
            if (!Cache::has('urlMenu')) {

                $menus = menus()->flatMap(fn($item) => $item);

                $url = [];

                foreach ($menus as $mm) {
                    $url[] = $mm->url;
                    foreach ($mm->subMenus as $sm) {
                        $url[] = $sm->url;
                    }
                }

                Cache::forever('urlMenu', $url);
            } else {
                $url = Cache::get('urlMenu');
            }

            return $url;
        }
    }

    if (!function_exists('responseSuccessDelete')) {
        function responseSuccessDelete()
        {
            return response()->json([
                'status' => 'success',
                'message' => 'Delete data Successfully',
            ]);
        }
    }

    if (!function_exists('getAmount')) {
        function getAmount($money)
        {
            $cleanString = preg_replace('/([^0-9\.,])/i', '', $money);
            $onlyNumbersString = preg_replace('/([^0-9])/i', '', $money);

            $separatorsCountToBeErased = strlen($cleanString) - strlen($onlyNumbersString) - 1;

            $stringWithCommaOrDot = preg_replace('/([,\.])/', '', $cleanString, $separatorsCountToBeErased);
            $removedThousandSeparator = preg_replace('/(\.|,)(?=[0-9]{3,}$)/', '', $stringWithCommaOrDot);

            return (float) str_replace(',', '.', $removedThousandSeparator);
        }
    }

    if (!function_exists('formatRupiah')) {
        function formatRupiah($angka, $prefix = null)
        {
            // Menghapus karakter selain angka dan koma
            $numberString = preg_replace('/[^,\d]/', '', $angka);

            // Memisahkan angka dengan pecahan desimal (jika ada)
            $split = explode(',', $numberString);
            $sisa = strlen($split[0]) % 3;

            // Bagian awal angka (sebelum ribuan)
            $rupiah = substr($split[0], 0, $sisa);

            // Kelompokkan angka dalam ribuan
            $ribuan = substr($split[0], $sisa);
            $ribuan = str_split($ribuan, 3);

            if (!empty($ribuan)) {
                $separator = $sisa ? '.' : '';
                $rupiah .= $separator . implode('.', $ribuan);
            }

            // Tambahkan pecahan desimal (jika ada)
            $rupiah = isset($split[1]) ? $rupiah . ',' . $split[1] : $rupiah;

            // Tambahkan prefix jika ada
            return $prefix === null ? $rupiah : ($rupiah != null ? $prefix . $rupiah : '');
        }
    }
}
