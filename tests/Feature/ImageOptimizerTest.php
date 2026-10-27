<?php

use App\Services\ImageOptimizer;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

/*
|--------------------------------------------------------------------------
| ImageOptimizer: lo que se guarda en public/uploads nunca lleva la extensión que mandó el cliente
|--------------------------------------------------------------------------
*/

beforeEach(function () {
    Storage::fake('uploads');
});

it('guarda una imagen real con la extensión de su contenido, aunque el nombre diga otra cosa', function () {
    $file = UploadedFile::fake()->image('ataque.html', 50, 50);

    $path = app(ImageOptimizer::class)->store($file, 'products');

    expect(pathinfo($path, PATHINFO_EXTENSION))->toBeIn(['jpg', 'png', 'webp']);
    Storage::disk('uploads')->assertExists($path);
});

it('rechaza un archivo que no es imagen y no guarda nada', function () {
    $file = UploadedFile::fake()->createWithContent('foto.jpg', '<?php echo "hola"; ?><script>alert(1)</script>');

    expect(fn () => app(ImageOptimizer::class)->store($file, 'products', field: 'photo'))
        ->toThrow(ValidationException::class);

    expect(Storage::disk('uploads')->allFiles())->toBeEmpty();
});

it('detecta el tipo por el contenido', function () {
    $optimizer = app(ImageOptimizer::class);

    $jpeg = UploadedFile::fake()->image('x.php', 10, 10); // el fake genera JPEG para extensiones desconocidas
    $text = UploadedFile::fake()->createWithContent('x.png', 'no soy una imagen');

    expect($optimizer->detectExtension($jpeg->getRealPath()))->toBe('jpg')
        ->and($optimizer->detectExtension($text->getRealPath()))->toBeNull();
});
