<?php

return [
    "required" => "El campo :attribute es obligatorio.",
    "string" => "El campo :attribute debe ser una cadena de caracteres.",
    "numeric" => "El campo :attribute debe ser numérico.",
    "integer" => "El campo :attribute debe ser un número entero.",
    "min" => [
        "numeric" => "El tamaño de :attribute debe ser de al menos :min.",
        "string" => "El campo :attribute debe contener al menos :min caracteres.",
    ],
    "max" => [
        "numeric" => "El campo :attribute no debe ser mayor que :max.",
        "string" => "El campo :attribute no debe ser mayor que :max caracteres.",
    ],
    "email" => "El campo :attribute no es un correo válido.",
    "unique" => "El campo :attribute ya ha sido registrado.",
    "in" => "El :attribute seleccionado no es válido.",
    'attributes' => [
        'name' => 'nombre',
        'percentage' => 'porcentaje',
        'email' => 'correo electrónico',
        'password' => 'contraseña',
        'zone_id' => 'zona',
        'family_id' => 'familia',
        'tax_id' => 'impuesto',
        'stock' => 'stock',
        'price' => 'precio',
        'role' => 'rol',
    ],
];
