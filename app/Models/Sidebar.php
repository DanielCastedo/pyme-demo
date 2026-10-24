<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Sidebar extends Model
{
    use HasFactory;

    protected $fillable = [
        'nombre', 'ruta', 'icono', 'parent_id', 'orden', 'rol_requerido', 'permiso_requerido',
    ];

    // Relación para obtener el menú padre
    public function parent()
    {
        return $this->belongsTo(sidebar::class, 'parent_id');
    }

    // Relación para obtener los submenús
    public function children()
    {
        return $this->hasMany(sidebar::class, 'parent_id')->orderBy('orden');
    }
}
