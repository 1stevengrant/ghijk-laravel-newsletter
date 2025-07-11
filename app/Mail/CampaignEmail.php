<?php

namespace App\Mail;

use App\Models\Campaign;
use App\Helpers\EmailHelper;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use App\Models\NewsletterSubscriber;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Queue\SerializesModels;
use Illuminate\Mail\Mailables\Envelope;

class CampaignEmail extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * Create a new message instance.
     */
    public function __construct(
        public Campaign $campaign,
        public NewsletterSubscriber $subscriber
    ) {
        //
    }

    /**
     * Get the message envelope.
     */
    public function envelope(): Envelope
    {
        return new Envelope(
            from: new Address(
                $this->campaign->newsletterList->from_email,
                $this->campaign->newsletterList->from_name
            ),
            subject: $this->campaign->subject ?? $this->campaign->name
        );
    }

    /**
     * Get the message content definition.
     */
    public function content(): Content
    {
        return new Content(
            view: 'emails.campaign',
            with: [
                'campaign' => $this->campaign,
                'subscriber' => $this->subscriber,
                'unsubscribeUrl' => $this->generateUnsubscribeUrl(),
                'trackingPixelUrl' => $this->generateTrackingPixelUrl(),
                'content' => EmailHelper::convertRelativeUrlsToAbsolute($this->campaign->content ?? ''),
            ]
        );
    }

    /**
     * Get the attachments for the message.
     *
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }

    /**
     * Generate unsubscribe URL for the subscriber
     */
    private function generateUnsubscribeUrl(): string
    {
        return route('newsletter.unsubscribe', [
            'token' => $this->subscriber->unsubscribe_token,
            'campaign' => $this->campaign->id,
        ]);
    }

    /**
     * Generate tracking pixel URL
     */
    private function generateTrackingPixelUrl(): string
    {
        return route('campaign.track.open', [
            'campaign' => $this->campaign->id,
            'subscriber' => $this->subscriber->id,
        ]);
    }
}
