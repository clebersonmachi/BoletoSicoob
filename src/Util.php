<?php

trait Util
{

    /**
    * Converte os caracteres especiais
    * @return string
    */
    protected function caracterEspecial($v)
    {
        return iconv('UTF-8', 'WINDOWS-1252', $v);
    }

    /**
    * Remove os / e . dos número dos documentos (CNPJ / CPF / RG / CEP)
    * @return string
    */
    public static function apenasNumeros($str)
    {
        return preg_replace('/[^0-9]/', '', (string) $str);
    }

    /**
    * Formata os números de acordo com os padrões da Sicoob
    * @return $numero
    * --------------------------------------------------------------------
    */
    public static function formataNumDoc($doc, $tamanho)
    {
        return str_pad($doc, $tamanho, '0', STR_PAD_LEFT);
    }
}
