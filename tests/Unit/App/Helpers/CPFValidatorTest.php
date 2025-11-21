<?php

require_once __DIR__ . '/../../../../app/Helpers/CPFValidator.php';

use PHPUnit\Framework\TestCase;

class CPFValidatorTest extends TestCase
{
    public function testDeveValidarTamanhoEFormatoDoCpf()
    {
        $this->assertFalse(CPFValidator('123.456.789'));
        $this->assertFalse(CPFValidator('00089312300'));
    }
    public function testNaoDeveAceitarCpfsComTodosDigitosRepetidos()
    {
        $this->assertFalse(CPFValidator('11111111111'));
        $this->assertFalse(CPFValidator('99999999999'));
    }

    public function testDeveRejeitarCpfComPrimeiroDigitoVerificadorIncorreto()
    {
        $this->assertFalse(CPFValidator('646.286.310-16')); 
    }

    public function testDeveValidarCpfsCorretos()
    {
        $this->assertFalse(CPFValidator('589.057.470-10'));
        $this->assertTrue(CPFValidator('292.444.680-50'));
    }

}
