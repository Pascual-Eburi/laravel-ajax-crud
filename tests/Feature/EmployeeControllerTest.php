<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use App\Models\Employee;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Testing\Fluent\AssertableJson;

class EmployeeControllerTest extends TestCase
{
    use RefreshDatabase;
    use WithFaker;

    /**
     * @test
     */
    public function test_it_can_store_an_employee(){
        Storage::fake('local'); 
        $file = UploadedFile::fake()->image('avatar.jpg');
        $file_name = $file->hashName();

        $data = [
            'first_name' => $this->faker->firstName,
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->email,
            'phone' => $this->faker->phoneNumber,
            'job_position' => $this->faker->jobTitle,
            'date_hired' => $this->faker->date,
            'avatar' => $file,
        ];


        $response = $this->json('POST', '/store', $data);
        $response->assertStatus(200)
            ->assertJson([
                'status' => 201,
                'message' => 'Employee added successfully',
            ]);


        $this->assertDatabaseHas('employees', [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'email' => $data['email'],
            'phone' => $data['phone'],
            'job_position' => $data['job_position'],
            'date_hired' => $data['date_hired'],
            'avatar' => 'avatars/' . $file_name
        ]);

        $employee = Employee::first();
        $this->assertNotNull($employee->avatar);
        Storage::disk('local')->assertExists($employee->avatar);
        $this->assertFileEquals($file, Storage::disk('local')->path($employee->avatar) );


    } 

    public function test_it_can_not_store_employee_if_invalid_data(){
        $data = [
            'first_name' => '', // first_name its required
            'last_name' => $this->faker->lastName,
            'email' => $this->faker->lastName, // is not an email
            'phone' => $this->faker->phoneNumber,
            'job_position' => $this->faker->jobTitle,
            'date_hired' => $this->faker->date,
            'avatar' => '',
        ];
        $response = $this->json('POST', '/store', $data);
        $response->assertStatus(422)
                    ->assertUnprocessable();
    }


    /**--------------------------------------------------------
     *  Show method tests
     *--------------------------------------*/
    public function test_it_can_show_an_employee(){
        $employee = Employee::factory()->create();
        $response = $this->json('GET', '/edit', ['id' => $employee->id]);
        $response->assertStatus(200);
        $response->assertJson([
            'id' => $employee->id,
            'first_name' => $employee->first_name,
            'last_name' => $employee->last_name,
            'email' => $employee->email,
            'phone' => $employee->phone
        ]);
    }

    /**
     * @test
     */
    public function test_it_returns_error_for_invalid_employee_id()
    {
        // Proporciona un ID de empleado que no existe en la base de datos
        $invalidEmployeeId = 999;

        // Realiza una solicitud GET al endpoint de mostrar empleado con el ID no válido
        $response = $this->json('GET', '/edit', ['id' => $invalidEmployeeId]);

        // Verifica que la respuesta contenga un código de estado 404 (not found) o 501
        $response->assertStatus(404) || $response->assertStatus(501);
        // Verifica que la respuesta contenga un mensaje de error en formato JSON
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->has('message')
        );
    }


    public function test_it_can_destroy_an_employee()
    {
        // Crea un empleado de ejemplo en la base de datos
        $employee = factory(Employee::class)->create();

        // Realiza una solicitud DELETE al endpoint de eliminar empleado
        $response = $this->json('DELETE', "/delete", ['id' => $employee->id]);

        // Verifica que la respuesta sea exitosa (código de estado 200)
        $response->assertStatus(200);

        // Verifica que el empleado se haya eliminado de la base de datos
        $this->assertDatabaseMissing('employees', [
            'id' => $employee->id,
        ]);

        // Verifica que el archivo de avatar se haya eliminado del almacenamiento simulado
        Storage::disk('public')->assertMissing('images/' . $employee->avatar);
    }

    /**
     * @test
     */
    public function it_returns_error_for_non_existing_employee()
    {
        // Proporciona un ID de empleado que no existe en la base de datos
        $nonExistingEmployeeId = 999;

        // Realiza una solicitud DELETE al endpoint de eliminar empleado con el ID no válido
        $response = $this->json('DELETE', "/delete", ['id'=>$nonExistingEmployeeId]);

        // Verifica que la respuesta contenga un código de estado 404 (not found)
        $response->assertStatus(404);

        // Verifica que la respuesta contenga un mensaje de error en formato JSON
        $response->assertJson([
            'message' => 'Employee Not Found',
        ]);
    }

    /**
     * @test
     */
    public function it_returns_error_for_internal_server_error()
    {
        // Simula un error interno al eliminar el empleado (puedes ajustar esta lógica según tu necesidad)
        $this->mock(Storage::class, function ($mock) {
            $mock->shouldReceive('delete')->andReturn(false);
        });

        // Crea un empleado de ejemplo en la base de datos
        $employee = factory(Employee::class)->create();

        // Realiza una solicitud DELETE al endpoint de eliminar empleado
        $response = $this->json('DELETE', "/delete" , ['id' => $employee->id]);

        $response->assertStatus(500);
        $response->assertJson(
            fn (AssertableJson $json) =>
            $json->has('message')
        );
    }

}


 
