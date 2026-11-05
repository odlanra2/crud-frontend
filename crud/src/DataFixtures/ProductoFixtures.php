<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\Entity\Producto;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class ProductoFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $datos = [
            ['Teclado mecánico RGB', 'Teclado con switches rojos y retroiluminación RGB.', '189900.00', 15, true],
            ['Mouse inalámbrico', 'Mouse ergonómico con receptor USB y Bluetooth.', '89900.00', 24, true],
            ['Monitor 24" Full HD', 'Monitor IPS de 24 pulgadas, 75 Hz y HDMI.', '699900.00', 8, true],
            ['Base para portátil', 'Base ajustable en aluminio para portátiles de 13" a 17".', '74900.00', 5, true],
            ['Hub USB-C 7 en 1', 'Adaptador con HDMI, USB 3.0, SD y carga PD.', '129900.00', 3, true],
            ['Audífonos Bluetooth', 'Audífonos over-ear con cancelación activa de ruido.', '349900.00', 10, true],
            ['Webcam 1080p', 'Cámara web con micrófono integrado y tapa de privacidad.', '159900.00', 6, true],
            ['Disco SSD 1 TB', 'Unidad SSD SATA III de 2.5 pulgadas.', '289900.00', 12, true],
            ['Memoria RAM 16 GB', 'Memoria DDR4 de 3200 MHz para portátil.', '219900.00', 2, true],
            ['Cargador GaN 65W', 'Cargador compacto de 65 W con dos puertos USB-C.', '119900.00', 18, true],
            ['Soporte para monitor', 'Brazo articulado para monitores de hasta 32 pulgadas.', '179900.00', 0, false],
            ['Alfombra para escritorio', 'Alfombra de tela antideslizante de 80 x 40 cm.', '49900.00', 30, true],
        ];

        foreach ($datos as [$nombre, $descripcion, $precio, $stock, $activo]) {
            $producto = (new Producto())
                ->setNombre($nombre)
                ->setDescripcion($descripcion)
                ->setPrecio($precio)
                ->setStock($stock)
                ->setActivo($activo);

            $manager->persist($producto);
        }

        $manager->flush();
    }
}
