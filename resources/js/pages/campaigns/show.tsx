import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FormEvent } from 'react';

const statusColors = {
    draft: 'bg-gray-100 text-gray-800',
    scheduled: 'bg-blue-100 text-blue-800',
    sending: 'bg-yellow-100 text-yellow-800',
    sent: 'bg-green-100 text-green-800',
};

export default function ShowCampaign({ campaign }: {
    campaign: App.Data.CampaignData
}) {
    const breadcrumbs: BreadcrumbItem[] = [
        {
            title: 'Dashboard',
            href: '/dashboard'
        },
        {
            title: 'Campaigns',
            href: '/campaigns'
        },
        {
            title: campaign.name,
            href: `/campaigns/${campaign.id}`
        }
    ];

    const { post, processing } = useForm();

    const handleSend = (e: FormEvent) => {
        e.preventDefault();
        post(route('campaigns.send', campaign.id));
    };

    const canSend = campaign.can_send;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={campaign.name} />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">{campaign.name}</h1>
                        <Badge className={statusColors[campaign.status as keyof typeof statusColors]}>
                            {campaign.status}
                        </Badge>
                    </div>
                    <div className="flex gap-2">
                        {canSend && (
                            <form onSubmit={handleSend}>
                                <Button type="submit" disabled={processing}>
                                    Send Now
                                </Button>
                            </form>
                        )}
                        {campaign.can_edit && (
                            <Button variant="outline" asChild>
                                <Link href={route('campaigns.edit', campaign.id)}>
                                    Edit
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Sent</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{campaign.sent_count}</div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Opens</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{campaign.opens}</div>
                            <p className="text-xs text-muted-foreground">
                                {campaign.open_rate}% open rate
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Clicks</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{campaign.clicks}</div>
                            <p className="text-xs text-muted-foreground">
                                {campaign.click_rate}% click rate
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Unsubscribes</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{campaign.unsubscribes}</div>
                            <p className="text-xs text-muted-foreground">
                                {campaign.unsubscribe_rate}% unsubscribe rate
                            </p>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle>Campaign Details</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <dl className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                            <div>
                                <dt className="font-medium">Newsletter List</dt>
                                <dd className="text-muted-foreground">
                                    {campaign.newsletter_list?.name || 'N/A'}
                                    {campaign.newsletter_list?.subscribers && (
                                        <span className="text-sm text-muted-foreground">
                                            {' '}({campaign.newsletter_list.subscribers.length} subscribers)
                                        </span>
                                    )}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-medium">Status</dt>
                                <dd>
                                    <Badge className={statusColors[campaign.status as keyof typeof statusColors]}>
                                        {campaign.status}
                                    </Badge>
                                </dd>
                            </div>
                            <div>
                                <dt className="font-medium">Scheduled At</dt>
                                <dd className="text-muted-foreground">
                                    {campaign.scheduled_at ? new Date(campaign.scheduled_at).toLocaleString() : 'N/A'}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-medium">Sent At</dt>
                                <dd className="text-muted-foreground">
                                    {campaign.sent_at ? new Date(campaign.sent_at).toLocaleString() : 'N/A'}
                                </dd>
                            </div>
                            <div>
                                <dt className="font-medium">Bounces</dt>
                                <dd className="text-muted-foreground">
                                    {campaign.bounces} ({campaign.bounce_rate}%)
                                </dd>
                            </div>
                        </dl>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}