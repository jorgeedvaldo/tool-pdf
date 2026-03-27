<?php

namespace App\Http\Controllers;

use Illuminate\Support\Str;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver;

class ArticleImageController extends Controller
{
    public function generate(string $title): string
    {
        $manager = new ImageManager(new Driver());

        // Background path
        $bgPath = storage_path('app/public/images/bg.png');

        if (file_exists($bgPath)) {
            $image = $manager->read($bgPath);
        } else {
            // Fallback se não existir
            $image = $manager->create(1200, 630)->fill('#131313');
        }

        // Quebrar texto automaticamente para 35 caracteres por linha
        $wrappedText = wordwrap($title, 35, "\n");

        // Adicionar texto
        $image->text($wrappedText, 600, 315, function ($font) {
            $fontPath = public_path('fonts/Montserrat-Bold.ttf');
            if (file_exists($fontPath)) {
                $font->file($fontPath);
            }
            $font->size(48);
            $font->color('#ffffff');
            $font->align('center');
            $font->valign('middle');
            $font->lineHeight(1.6);
        });

        // Nome do ficheiro (conforme solicitado, na pasta posts)
        $filename = 'images/posts/' . Str::uuid() . '.png';

        // Caminho completo
        $path = storage_path('app/public/' . $filename);

        // Criar diretório se não existir
        if (!file_exists(dirname($path))) {
            mkdir(dirname($path), 0755, true);
        }

        // Guardar imagem
        $image->save($path);

        return $filename;
    }
}
