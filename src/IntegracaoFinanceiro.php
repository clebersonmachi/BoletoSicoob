<?php

class IntegracaoFinanceiro 
{
    /**
    * Cria o model Financeiro
    * @return QueryBuilder
    * --------------------------------------------------------------------
    */
    public static function mkFinanceiroModel($completo = true)
    {
        $dataInicio = date('Y-m-').'01';
        $dataFim = date('Y-m-t');
        $model = $completo ? Financeiro::with('contato') : new Financeiro;

        return $model->where_not_null('boleto_codigo')
                    ->where_not_null('boleto_numero')
                    ->where_id_financeiro_forma_pagamento(Financeiro::ID_FORMA_PAGAMENTO_BOLETO)
                    ->where_ativo(1)
                    ->where_boleto_situacao(0)
                    ->where_situacao(0)
                    ->where_tipo(1)
                    ->where_between('data_vencimento', $dataInicio, $dataFim);
    }

    /**
    * Verifica quantos boletos estão pendentes
    * @return Number
    * --------------------------------------------------------------------
    */
    public static function getPendencias()
    {
        $pendencias = static::mkFinanceiroModel(false)->get(array('id'));

        if ($pendencias) {
            $retorno = array();
            
            foreach ($pendencias as $p) {
                array_push($retorno, $p->id);
            }

            return implode(', ', $retorno);
        }

        return null;
    }
}
