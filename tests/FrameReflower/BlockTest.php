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
        //file_put_contents('test.pdf', $output);

        $tree = $pdf->getTree();

        // node 2 - grandparent
        $gp = $tree->get_frame(2);
        $expectedWidth = 543.9685;
        $actualWidth = $gp->get_containing_block('w');
        $this->assertEquals($expectedWidth, $actualWidth, '', 0.0001);

        // node 4 - parent of "ONE"
        $oneParent = $tree->get_frame(4);
        $expectedWidth = 225;
        $actualWidth = $oneParent->get_containing_block('w');
        $this->assertEquals($expectedWidth, $actualWidth, '', 0.0001);

        // node 7 - parent of "two"
        $twoParent = $tree->get_frame(7);
        $expectedWidth = 225;
        $actualWidth = $twoParent->get_containing_block('w');
        $this->assertEquals($expectedWidth, $actualWidth, '', 0.0001);

        // node 10 - parent of "three"
        $threeParent = $tree->get_frame(10);
        $expectedWidth = 225;
        $actualWidth = $threeParent->get_containing_block('w');
        $this->assertEquals($expectedWidth, $actualWidth, '', 0.0001);

        // check that parent nodes line up correctly

        // node 4 and node 7 should be on same line - different x, same y

        $this->assertEquals($oneParent->get_position('y'), $twoParent->get_position('y'), 'One and Two parents should be on same line but are not');
        $this->assertNotEquals($oneParent->get_position('x'), $twoParent->get_position('x'), 'One and Two parents should have different X positions but do not');

        // node 4 and node 10 should be in same column - different y, same x
        $this->assertNotEquals($oneParent->get_position('y'), $threeParent->get_position('y'), 'One and Three parents should not be on same line but are');
        $this->assertEquals($oneParent->get_position('x'), $threeParent->get_position('x'), 'One and Three parents should not have different X positions but do');
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
