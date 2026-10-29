<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Menjamin preventLazyLoading berlaku pada SEMUA model hasil query.
 *
 * Latar belakang — bug pada Laravel 13:
 *
 * `Illuminate\Database\Eloquent\Builder::hydrate()` hanya menyalin flag static
 * ke property instance bila hasil query lebih dari satu baris:
 *
 *     $model = $instance->newFromBuilder($item);
 *
 *     if (count($items) > 1) {
 *         $model->preventsLazyLoading = Model::preventsLazyLoading();
 *     }
 *
 * Karena `preventsLazyLoading` dibaca dari property instance (bukan static),
 * model yang diperoleh lewat first(), find(), atau route model binding selalu
 * bernilai false — sehingga akses relasi tanpa eager load TIDAK melempar pada
 * halaman-halaman tersebut.
 *
 * Akibatnya bug lazy loading hanya terlihat pada halaman yang memakai
 * get()/paginate(), dan lolos pada halaman detail yang justru paling sering
 * dibuka oleh pengguna.
 *
 * Trait ini menutup celah tersebut dengan menetapkan flag setiap kali model
 * dibangun dari hasil query, berapa pun jumlah barisnya.
 */
trait EnforcesLazyLoading
{
    /**
     * @param  array<string, mixed>  $attributes
     * @return static
     */
    public function newFromBuilder($attributes = [], $connection = null)
    {
        $model = parent::newFromBuilder($attributes, $connection);

        if (Model::preventsLazyLoading()) {
            $model->preventsLazyLoading = true;
        }

        return $model;
    }

    /**
     * Model yang baru dibuat juga harus diperiksa, agar relasi yang diakses
     * pada request yang sama tidak lolos dari pengawasan.
     *
     * @param  array<string, mixed>  $attributes
     * @param  string|null  $connection
     * @return static
     */
    public function newInstance($attributes = [], $exists = false)
    {
        $model = parent::newInstance($attributes, $exists);

        if (Model::preventsLazyLoading()) {
            $model->preventsLazyLoading = true;
        }

        return $model;
    }
}
