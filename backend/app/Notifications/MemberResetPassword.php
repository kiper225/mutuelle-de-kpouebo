<?php

namespace App\Notifications;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

/** Email de réinitialisation en français, avec un lien vers le site Vue. */
class MemberResetPassword extends ResetPassword
{
    protected function buildMailMessage($url)
    {
        $minutes = config('auth.passwords.'.config('auth.defaults.passwords').'.expire');

        return (new MailMessage)
            ->subject('Réinitialisation de votre mot de passe')
            ->greeting('Bonjour,')
            ->line('Vous recevez ce message car une réinitialisation du mot de passe a été demandée pour votre compte.')
            ->action('Choisir un nouveau mot de passe', $url)
            ->line("Ce lien expire dans {$minutes} minutes.")
            ->line('Si vous n\'êtes pas à l\'origine de cette demande, ignorez ce message : votre mot de passe ne changera pas.')
            ->salutation('La Mutuelle de Développement de Kpouèbo');
    }

    protected function resetUrl($notifiable)
    {
        return rtrim(config('mutuelle.frontend_url'), '/').'/reinitialiser-mot-de-passe?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}