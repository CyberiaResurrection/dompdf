<?php

namespace Dompdf\Tests\FrameReflower;

use Dompdf\Css\Style;
use Dompdf\Css\Stylesheet;
use Dompdf\Dompdf;
use Dompdf\FontMetrics;
use Dompdf\FrameDecorator\Block;
use Dompdf\FrameDecorator\Page;
use Dompdf\Tests\TestCase;
use Mockery;

class BlockTest extends TestCase
{
    /**
     * @throws \Dompdf\Exception
     * @throws \Exception
     */
    public function testHandleBoxSizing()
    {
        $html = file_get_contents(__DIR__ .'/header-strip.html');

        $pdf = new Dompdf();
        $pdf->loadHtml($html);

        $pdf->render();

        $foo = $pdf->getTree()->get_frame(8);
        $bar = $pdf->getTree()->get_frame(11);

        $this->assertEquals($foo->get_position('y'), $bar->get_position('y') , 'Foo and bar are not on the same line and should be.');
        $this->assertNotEquals($foo->get_position('x'), $bar->get_position('x') , 'Foo and bar start at the same position, and shouldn\'t.');
    }
}
