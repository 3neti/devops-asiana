<?php

namespace App\SuccessorAppointments;

use Illuminate\Support\Facades\File;
use JsonException;
use UnexpectedValueException;

final class SuccessorAppointmentRepository
{
    /** @throws JsonException */
    public function current(): SuccessorAppointmentDefinition
    {
        $definition = json_decode(File::get(resource_path('institution/successor-appointments.json')), true, flags: JSON_THROW_ON_ERROR);

        if (! is_array($definition)) {
            throw new UnexpectedValueException('The Successor Appointment definition must be a JSON object.');
        }

        return SuccessorAppointmentDefinition::fromArray($definition);
    }
}
