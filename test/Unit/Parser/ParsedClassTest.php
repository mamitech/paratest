<?php

declare(strict_types=1);

namespace ParaTest\Tests\Unit\Parser;

use ParaTest\Parser\ParsedClass;
use ParaTest\Parser\ParsedFunction;
use ParaTest\Tests\TestBase;

class ParsedClassTest extends TestBase
{
    /** @var ParsedClass  */
    protected $class;
    /** @var ParsedFunction[]  */
    protected $methods;

    public function setUp(): void
    {
        $this->methods = [
            new ParsedFunction(
                '/**
              * @group group1
              */',
                'public',
                'testFunction'
            ),
            new ParsedFunction(
                '/**
              * @group group2
              */',
                'public',
                'testFunction2'
            ),
            new ParsedFunction('', 'public', 'testFunction3'),
        ];
        $this->class   = new ParsedClass('', 'MyTestClass', '', $this->methods);
    }

    public function testGetMethodsReturnsMethods(): void
    {
        $this->assertEquals($this->methods, $this->class->getMethods());
    }

    public function testGetMethodsMultipleAnnotationsReturnsMethods(): void
    {
        $goodMethod     = new ParsedFunction(
            '/**
              * @group group1
              */',
            'public',
            'testFunction'
        );
        $goodMethod2    = new ParsedFunction(
            '/**
              * @group group2
              */',
            'public',
            'testFunction2'
        );
        $badMethod      = new ParsedFunction(
            '/**
              * @group group3
              */',
            'public',
            'testFunction2'
        );
        $annotatedClass = new ParsedClass('', 'MyTestClass', '', [$goodMethod, $goodMethod2, $badMethod]);
        $methods        = $annotatedClass->getMethods(['group' => 'group1,group2']);
        $this->assertEquals([$goodMethod, $goodMethod2], $methods);
    }

    public function testGetMethodsExceptsAdditionalAnnotationFilter(): void
    {
        $group1 = $this->class->getMethods(['group' => 'group1']);
        $this->assertCount(1, $group1);
        $this->assertEquals($this->methods[0], $group1[0]);
    }
}
