<?php

namespace App\Filament\Resources\Registrations\Schemas;

use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Support\Str;

class RegistrationForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('fullname')
                    ->label('Full name')
                    ->required()
                    ->maxLength(100),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required()
                    ->unique(ignoreRecord: true),
                TextInput::make('phone')
                    ->tel()
                    ->required()
                    ->maxLength(30),
                // value inside the QR code: secret, made automatically for new guests
                Hidden::make('token')
                    ->default(fn () => (string) Str::uuid()),
                DateTimePicker::make('email_sent_at')
                    ->label('Email sent'),
                DateTimePicker::make('checked_in_at')
                    ->label('Checked in'),
            ]);
    }
}
