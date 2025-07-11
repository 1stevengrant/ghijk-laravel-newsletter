import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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
        newsletter_list_id: '',
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
                        <CardTitle>Create New Campaign - Step 1</CardTitle>
                        <CardDescription>
                            Set up the basic information for your email campaign.
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

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing}>
                                    Continue to Content
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