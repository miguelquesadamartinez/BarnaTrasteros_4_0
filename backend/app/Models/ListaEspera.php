<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ListaEspera extends Model
{
    protected $table = 'lista_espera';

    protected $fillable = ['nombre', 'telefono', 'tamanyo'];
}
