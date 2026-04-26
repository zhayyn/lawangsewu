<?php

namespace Tests\Unit\Exceptions;

use App\Exceptions\WaRuntimeException;
use App\Exceptions\SippDataException;
use App\Exceptions\InvalidInputException;
use App\Exceptions\DatabaseException;
use PHPUnit\Framework\TestCase;

class CustomExceptionsTest extends TestCase
{
    public function test_wa_runtime_exception_stores_metadata(): void
    {
        $exception = new WaRuntimeException(
            message: 'Connection failed',
            code: 502,
            detail: 'Timeout after 30s',
            isRetryable: true
        );

        $this->assertEquals('Connection failed', $exception->getMessage());
        $this->assertEquals(502, $exception->getStatusCode());
        $this->assertEquals('Timeout after 30s', $exception->getDetail());
        $this->assertTrue($exception->isRetryable());
    }

    public function test_wa_runtime_exception_to_array(): void
    {
        $exception = new WaRuntimeException(
            message: 'Connection failed',
            code: 502,
            detail: 'Timeout',
            isRetryable: false
        );

        $array = $exception->toArray();

        $this->assertArrayHasKey('error', $array);
        $this->assertArrayHasKey('code', $array);
        $this->assertArrayHasKey('detail', $array);
        $this->assertArrayHasKey('retryable', $array);
        $this->assertArrayHasKey('timestamp', $array);
        $this->assertEquals('Connection failed', $array['error']);
        $this->assertFalse($array['retryable']);
    }

    public function test_sipp_data_exception_stores_source(): void
    {
        $exception = new SippDataException(
            message: 'Data sync failed',
            code: 503,
            source: 'SIPP Database',
            isRetryable: true
        );

        $this->assertEquals('Data sync failed', $exception->getMessage());
        $this->assertEquals(503, $exception->getStatusCode());
        $this->assertEquals('SIPP Database', $exception->getSource());
        $this->assertTrue($exception->isRetryable());
    }

    public function test_sipp_data_exception_to_array(): void
    {
        $exception = new SippDataException(
            message: 'Database error',
            code: 503,
            source: 'SIPP',
            isRetryable: false
        );

        $array = $exception->toArray();

        $this->assertEquals('Database error', $array['error']);
        $this->assertEquals('SIPP', $array['source']);
        $this->assertFalse($array['retryable']);
    }

    public function test_invalid_input_exception_stores_errors(): void
    {
        $errors = [
            'email' => ['Email is invalid'],
            'name' => ['Name is required'],
        ];

        $exception = new InvalidInputException($errors);

        $this->assertEquals($errors, $exception->getErrors());
    }

    public function test_invalid_input_exception_to_response(): void
    {
        $errors = ['email' => ['Invalid email']];
        $exception = new InvalidInputException($errors, 'Validation failed', 422);

        $response = $exception->toResponse(request());

        $this->assertEquals(422, $response->getStatusCode());
        
        $json = json_decode($response->getContent(), true);
        $this->assertArrayHasKey('error', $json);
        $this->assertArrayHasKey('errors', $json);
        $this->assertEquals($errors, $json['errors']);
    }

    public function test_database_exception_stores_operation(): void
    {
        $exception = new DatabaseException(
            message: 'Insert failed',
            code: 500,
            operation: 'create_user',
            isRetryable: false
        );

        $this->assertEquals('Insert failed', $exception->getMessage());
        $this->assertEquals('create_user', $exception->getOperation());
        $this->assertFalse($exception->isRetryable());
    }

    public function test_database_exception_to_array(): void
    {
        $exception = new DatabaseException(
            message: 'Duplicate entry',
            code: 409,
            operation: 'create_user',
            isRetryable: false
        );

        $array = $exception->toArray();

        $this->assertEquals('Duplicate entry', $array['error']);
        $this->assertEquals('create_user', $array['operation']);
        $this->assertEquals(409, $array['code']);
        $this->assertFalse($array['retryable']);
    }

    public function test_exceptions_include_timestamp(): void
    {
        $exception = new WaRuntimeException('Error', 502);
        $array = $exception->toArray();

        $this->assertArrayHasKey('timestamp', $array);
        $this->assertNotEmpty($array['timestamp']);
    }

    public function test_exception_default_message(): void
    {
        $waEx = new WaRuntimeException();
        $this->assertEquals('Gagal terhubung ke WA runtime', $waEx->getMessage());

        $sippEx = new SippDataException();
        $this->assertEquals('Gagal mengakses data SIPP', $sippEx->getMessage());
    }
}
