import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow
} from '@/components/ui/table';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import SendNow from '@/components/campaigns/send-now';
import { useEcho } from '@laravel/echo-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard'
    },
    {
        title: 'Campaigns',
        href: '/campaigns'
    }
];

const statusColors = {
    draft: 'bg-gray-100 text-gray-800',
    scheduled: 'bg-blue-100 text-blue-800',
    sending: 'bg-yellow-100 text-yellow-800',
    sent: 'bg-green-100 text-green-800',
};

export default function CampaignsIndex({ campaigns }: {
    campaigns: App.Data.CampaignData[]
}) {
    const [campaignList, setCampaignList] = useState<App.Data.CampaignData[]>(campaigns);

    useEcho('campaigns', 'CampaignStatusChanged', (e: { campaign: App.Data.CampaignData }) => {
        setCampaignList(prevCampaigns =>
            prevCampaigns.map(campaign =>
                campaign.id === e.campaign.id
                    ? { ...campaign, ...e.campaign }
                    : campaign
            )
        );
    });
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Campaigns" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4 overflow-x-auto">
                <div>
                    <Button asChild>
                        <Link href={route('campaigns.create')}>
                            Create Campaign
                        </Link>
                    </Button>
                </div>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Name</TableHead>
                            <TableHead>Status</TableHead>
                            <TableHead>List</TableHead>
                            <TableHead>Scheduled</TableHead>
                            <TableHead>Sent</TableHead>
                            <TableHead>Stats</TableHead>
                            <TableHead>Actions</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {campaignList.map((campaign: App.Data.CampaignData) => (
                            <TableRow key={campaign.id}>
                                <TableCell className="font-medium">{campaign.name}</TableCell>
                                <TableCell>
                                    <Badge className={statusColors[campaign.status as keyof typeof statusColors]}>
                                        <span className="capitalize">{campaign.status}</span>
                                    </Badge>
                                </TableCell>
                                <TableCell className="capitalize">
                                    {campaign.newsletter_list?.name || 'N/A'}
                                    {campaign.newsletter_list?.subscribers_count !== undefined && (
                                        <span className="text-gray-500 ml-1">
                                            ({campaign.newsletter_list.subscribers_count} subscribers)
                                        </span>
                                    )}
                                </TableCell>
                                <TableCell>
                                    {campaign.scheduled_at_friendly}
                                </TableCell>
                                <TableCell>
                                    {campaign.sent_at_friendly}
                                </TableCell>
                                <TableCell>
                                    <div className="text-sm">
                                        <div>Sent: {campaign.sent_count}</div>
                                        <div>Opens: {campaign.opens} ({campaign.open_rate}%)</div>
                                        <div>Clicks: {campaign.clicks} ({campaign.click_rate}%)</div>
                                    </div>
                                </TableCell>
                                <TableCell>
                                    <div className="space-x-2">
                                        {campaign.can_send && (
                                            <SendNow campaign={campaign} />
                                        )}
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            asChild>
                                            <Link href={route('campaigns.show', campaign.id)}>
                                                View
                                            </Link>
                                        </Button>
                                        {campaign.can_edit && (
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                asChild>
                                                <Link href={route('campaigns.edit', campaign.id)}>
                                                    Edit
                                                </Link>
                                            </Button>
                                        )}
                                    </div>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </AppLayout>
    );
}
