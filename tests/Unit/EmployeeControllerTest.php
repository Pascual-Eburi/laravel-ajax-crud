<?php

namespace Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Employee; // Asegúrate de importar el modelo Employee

class EmployeeControllerTest extends TestCase{
    use RefreshDatabase;

    /**
     * @test
     */
    public function it_can_get_all_employees(){
        $employee1 = Employee::factory()->create();
        $employee2 = Employee::factory()->create();

        $response = $this->get('employes');
        $data = json_decode($response->getContent(), true);

        $this->assertCount(2, $data['data']);
        $this->assertEquals($employee1->email, $data['data'][0][3]);
        $this->assertEquals($employee2->email, $data['data'][1][3]);
    }

    /**
     * @test
     */
    public function it_does_not_return_employees_when_none_exist(){
        $response = $this->get('employes');
        $data = json_decode($response->getContent(), true);
        $this->assertEmpty($data['data']);
    }
}

