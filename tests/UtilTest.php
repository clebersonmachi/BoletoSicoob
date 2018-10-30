<?php

use PHPUnit\Framework\TestCase;

class UtilTest extends TestCase
{
    /**
     * @test
     * @covers \Util::caracterEspecial
     */
    public function CaracterEspecialConverteCorretamente()
    {
        $util = new class {
            use Util;
        };

        $class = new \ReflectionClass($util);
        $method = $class->getMethod('caracterEspecial');
        $method->setAccessible(true);
        
        $result = $method->invokeArgs($util, ['áéíóú']);
        
        $this->assertNotEmpty($result);
    }

    /**
     * @test
     * @covers \Util::apenasNumeros
     */
    public function apenasNumeroRetornaSomenteNumeros()
    {
        $valor = 'a1b2c3';
        $esperado = '123';

        $this->assertEquals(Util::apenasNumeros($valor), $esperado);
    }

    /**
     * @test
     * @covers \Util::formataNumDoc
     */
    public function formataNumDocPreencheComZerosEsquerda()
    {
        $valor = '1';
        $esperado = '0001';

        $resultado = Util::formataNumDoc($valor, 4);

        $this->assertEquals($esperado, $resultado);
    }
}
