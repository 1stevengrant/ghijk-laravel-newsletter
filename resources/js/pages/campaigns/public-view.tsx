import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface PublicCampaignData {
    name: string;
    subject: string;
    content: string;
    blocks?: any[];
    sent_at: string;
    shortcode: string;
}

export default function PublicCampaignView({ campaign }: {
    campaign: PublicCampaignData
}) {
    return (
        <div className="min-h-screen bg-gray-50 py-8">
            <Head title={campaign.subject || campaign.name} />
            
            <div className="mx-auto max-w-4xl px-4 sm:px-6 lg:px-8">
                <div className="mb-8 text-center">
                    <h1 className="text-3xl font-bold text-gray-900">
                        {campaign.subject || campaign.name}
                    </h1>
                    {campaign.sent_at && (
                        <p className="mt-2 text-sm text-gray-600">
                            Sent on {new Date(campaign.sent_at).toLocaleDateString('en-US', {
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric',
                            })}
                        </p>
                    )}
                </div>

                <Card className="mx-auto max-w-3xl">
                    <CardContent className="p-8">
                        {campaign.content ? (
                            <div 
                                className="prose prose-lg max-w-none"
                                dangerouslySetInnerHTML={{ __html: campaign.content }}
                            />
                        ) : (
                            <div className="text-center text-gray-500">
                                <p>This campaign has no content to display.</p>
                            </div>
                        )}
                    </CardContent>
                </Card>

                <div className="mt-8 text-center">
                    <p className="text-sm text-gray-500">
                        Campaign ID: {campaign.shortcode}
                    </p>
                </div>
            </div>
        </div>
    );
}