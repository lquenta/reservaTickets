<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContactFormCaptchaTest extends TestCase
{
    use RefreshDatabase;

    public function test_contact_section_links_to_whatsapp(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('https://wa.me/59164066996?text=necesito%20ayuda%20por%20favor', false)
            ->assertSee('Escribir por WhatsApp')
            ->assertDontSee('contact-name', false);
    }
}
