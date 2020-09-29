<?php

namespace Dompdf\Tests\FrameReflower;

use Dompdf\Css\Style;
use Dompdf\Css\Stylesheet;
use Dompdf\Dompdf;
use Dompdf\FontMetrics;
use Dompdf\Frame;
use Dompdf\FrameDecorator\Block;
use Dompdf\FrameDecorator\Page;
use Dompdf\Tests\TestCase;
use Mockery;

class BlockTest extends TestCase
{
    /**
     * Characterise some weirdness in the block reflower when divs were being printed over the top of each other,
     * rather than alongside each other
     *
     * @runInSeparateProcess
     * @throws \Dompdf\Exception
     * @throws \Exception
     */
    public function testCharacteriseDivOverprinting()
    {
        $html = file_get_contents(__DIR__ .'/header-strip.html');

        $pdf = new Dompdf();
        $pdf->loadHtml($html);

        $pdf->render();

        $foo = $pdf->getTree()->get_frame(8);
        $this->assertNotNull($foo);
        $bar = $pdf->getTree()->get_frame(11);
        $this->assertNotNull($bar);

        $fooExpected = [34.015748031496059, 72.876548031496057, 'x' => 34.015748031496059, 'y' => 72.876548031496057];
        $fooActual = $foo->get_position();
        $this->assertEquals($fooExpected, $fooActual);

        $barExpected = [170.00787401574803, 72.876548031496057, 'x' => 170.00787401574803, 'y' => 72.876548031496057];
        $barActual = $bar->get_position();
        $this->assertEquals($barExpected, $barActual);
    }

    /**
     * @throws \Dompdf\Exception
     */
    public function testCharacteriseVerticalDivOverprinting()
    {
        $html = file_get_contents(__DIR__ .'/overlap-simple.html');

        $pdf = new Dompdf();
        $pdf->loadHtml($html);

        $pdf->render();

        $output = $pdf->output();
        file_put_contents('test.pdf', $output);
    }

    /**
     * @throws \Dompdf\Exception
     * @throws \Exception
     */
    public function testCharacteriseMultilevelDivOverprinting()
    {
        $html = file_get_contents(__DIR__ .'/overlap-2.html');

        $pdf = new Dompdf();
        $pdf->loadHtml($html);

        $pdf->render();

        $output = $pdf->output();
        file_put_contents('test.pdf', $output);

        $tree = $pdf->getTree();
        $reflec = new \ReflectionClass($tree);
        $prop = $reflec->getProperty('_registry');
        $prop->setAccessible(true);

        $res = $prop->getValue($tree);

        // initially, dig out "Foo" and "Baz" frames, then go from there
        $frames = [];
        $expected = ['Foo', 'Baz'];

        foreach ($res as $line) {
            $content = $line->get_node()->textContent;

            if (in_array($content, $expected)) {
                $frames[$content] = $line->get_parent()->get_parent();
            }
        }

        // Ids of frames in question are 18 and 51
        /** @var Frame $fooBlock */
        $fooBlock = $frames['Foo']->get_position();
        $bazBlock = $frames['Baz']->get_position();

        $this->assertGreaterThan($fooBlock['y'], $bazBlock['y'], 'Foo block must be higher on page than Baz block');
    }
}
