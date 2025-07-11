import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard'
    }
];

export default function Dashboard({
                                      campaignCount,
                                      draftCampaigns,
                                      scheduledCampaigns,
                                      sentCampaigns,
                                      listCount,
                                      subscriberCount
                                  }: {
    campaignCount: number;
    draftCampaigns: number;
    scheduledCampaigns: number;
    sentCampaigns: number;
    listCount: number;
    subscriberCount: number;
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <div className="grid gap-6 lg:grid-cols-3">
                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Total Campaigns</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{campaignCount}</div>
                            <div className="space-x-2">
                                <Badge className="bg-blue-600 text-white">
                                    {draftCampaigns} Drafts
                                </Badge>
                                <Badge className="bg-gray-200 text-black">
                                    {scheduledCampaigns} Scheduled
                                </Badge>
                                <Badge className="bg-green-500 text-white">{sentCampaigns} Sent</Badge>
                            </div>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Newsletter Lists</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{listCount}</div>
                            <p className="text-xs text-muted-foreground">
                                Subscriber lists managed
                            </p>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle className="text-sm font-medium">Active Subscribers</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="text-2xl font-bold">{subscriberCount}</div>
                            <p className="text-xs text-muted-foreground">
                                Subscribed users
                            </p>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
