import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { FormEvent } from 'react';

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
        title: 'Create',
        href: '/campaigns/create'
    }
];

export default function CreateCampaign({ lists }: {
    lists: { id: number; name: string; subscribers_count: number }[]
}) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        subject: '',
        content: '',
        newsletter_list_id: '',
        status: 'draft',
        scheduled_at: '',
    });

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        post(route('campaigns.store'));
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Create Campaign" />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Create New Campaign</CardTitle>
                        <CardDescription>
                            Create a new email campaign for your newsletter list.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <Label htmlFor="name">Campaign Name</Label>
                                <Input
                                    id="name"
                                    type="text"
                                    value={data.name}
                                    onChange={(e) => setData('name', e.target.value)}
                                    placeholder="Enter campaign name"
                                />
                                {errors.name && <p className="text-red-500 text-sm">{errors.name}</p>}
                            </div>

                            <div>
                                <Label htmlFor="subject">Email Subject</Label>
                                <Input
                                    id="subject"
                                    type="text"
                                    value={data.subject}
                                    onChange={(e) => setData('subject', e.target.value)}
                                    placeholder="Enter email subject"
                                />
                                {errors.subject && <p className="text-red-500 text-sm">{errors.subject}</p>}
                            </div>

                            <div>
                                <Label htmlFor="content">Email Content</Label>
                                <Textarea
                                    id="content"
                                    value={data.content}
                                    onChange={(e) => setData('content', e.target.value)}
                                    placeholder="Enter your email content here..."
                                    rows={8}
                                />
                                {errors.content && <p className="text-red-500 text-sm">{errors.content}</p>}
                            </div>

                            <div>
                                <Label htmlFor="newsletter_list_id">Newsletter List</Label>
                                <Select
                                    value={data.newsletter_list_id}
                                    onValueChange={(value) => setData('newsletter_list_id', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select a newsletter list" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {lists.map((list) => (
                                            <SelectItem key={list.id} value={list.id.toString()}>
                                                {list.name} ({list.subscribers_count} subscribers)
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                {errors.newsletter_list_id && <p className="text-red-500 text-sm">{errors.newsletter_list_id}</p>}
                            </div>

                            <div>
                                <Label htmlFor="status">Status</Label>
                                <Select
                                    value={data.status}
                                    onValueChange={(value) => setData('status', value)}
                                >
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select status" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft">Draft</SelectItem>
                                        <SelectItem value="scheduled">Scheduled</SelectItem>
                                    </SelectContent>
                                </Select>
                                {errors.status && <p className="text-red-500 text-sm">{errors.status}</p>}
                            </div>

                            {data.status === 'scheduled' && (
                                <div>
                                    <Label htmlFor="scheduled_at">Scheduled Date & Time</Label>
                                    <Input
                                        id="scheduled_at"
                                        type="datetime-local"
                                        value={data.scheduled_at}
                                        onChange={(e) => setData('scheduled_at', e.target.value)}
                                    />
                                    {errors.scheduled_at && <p className="text-red-500 text-sm">{errors.scheduled_at}</p>}
                                </div>
                            )}

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Create Campaign
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={route('campaigns.index')}>
                                        Cancel
                                    </Link>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}