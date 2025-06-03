<?php

namespace Tests\Feature;

use Tests\TestCase;

class BeneficioControllerTest extends TestCase
{
    public function test_devuelve_lista_de_beneficios_filtrados()
    {
        $response = $this->get('/api/beneficios-filtrados');

        $response->assertStatus(200);

        $response->assertSeeText('Subsidio Familiar (SUF)');

    }
}
