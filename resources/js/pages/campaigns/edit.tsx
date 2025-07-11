import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import BlockBuilder, { type Block } from '@/components/editor/block-builder';
import EmailPreview from '@/components/campaigns/email-preview';
import { FormEvent, useState } from 'react';
import InputError from '@/components/input-error';
import { convertBlocksToHtml, initializeBlocksFromCampaign, shouldUseBlocksMode } from '@/utils/block-utils';

export default function EditCampaign({ campaign, lists }: {
    campaign: App.Data.CampaignData;
    lists: { id: number; name: string; subscribers_count: number }[];
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
        },
        {
            title: 'Edit',
            href: `/campaigns/${campaign.id}/edit`
        }
    ];

    const { data, setData, processing, errors } = useForm({
        name: campaign.name,
        subject: campaign.subject || '',
        content: campaign.content || '',
        newsletter_list_id: campaign.newsletter_list_id.toString(),
        status: campaign.status,
        scheduled_at: campaign.scheduled_at ? campaign.scheduled_at.slice(0, 16) : '',
        blocks: campaign.blocks || null
    });

    // Initialize blocks and content type based on campaign data
    const [blocks, setBlocks] = useState<Block[]>(() => initializeBlocksFromCampaign(campaign));
    const [contentType, setContentType] = useState<'simple' | 'blocks'>(() => shouldUseBlocksMode(campaign) ? 'blocks' : 'simple');

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();

        // Prepare the data to submit
        let submitData = {
            name: data.name,
            subject: data.subject,
            content: data.content,
            newsletter_list_id: data.newsletter_list_id,
            status: data.status,
            scheduled_at: data.scheduled_at,
            blocks: data.blocks
        };

        // Convert blocks to HTML if using block builder
        if (contentType === 'blocks') {
            const htmlContent = convertBlocksToHtml(blocks);
            submitData = {
                ...submitData,
                content: htmlContent,
                blocks: blocks.map(block => ({
                    ...block,
                    settings: block.settings ? {
                        imageId: block.settings.imageId ?? null,
                        imageUrl: block.settings.imageUrl ?? null,
                        imageAlt: block.settings.imageAlt ?? null,
                        imagePath: block.settings.imagePath ?? null,
                        listType: block.settings.listType ?? null,
                        quoteAuthor: block.settings.quoteAuthor ?? null,
                    } : null
                }))
            };
        }

        router.put(route('campaigns.update', campaign.id), submitData);
    };


    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Edit ${campaign.name}`} />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Edit Campaign</CardTitle>
                        <CardDescription>
                            Update the campaign details.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        {!campaign.can_edit && (
                            <div className="mb-4 p-4 border border-yellow-200 bg-yellow-50 rounded-md">
                                <p className="text-yellow-800">
                                    This campaign cannot be edited because it has already been sent.
                                </p>
                            </div>
                        )}
                        <form onSubmit={handleSubmit}>
                            <div className="grid grid-cols-3 gap-4">
                                <div className="col-span-2">
                                    <Label htmlFor="content">Email Content</Label>
                                    <Tabs value={contentType}
                                          onValueChange={(value) => setContentType(value as 'simple' | 'blocks')}>
                                        <TabsList className="grid w-full grid-cols-2">
                                            <TabsTrigger value="simple" disabled={!campaign.can_edit}>Simple
                                                Editor</TabsTrigger>
                                            <TabsTrigger value="blocks" disabled={!campaign.can_edit}>Block
                                                Builder</TabsTrigger>
                                        </TabsList>

                                        <TabsContent value="simple" className="mt-4">
                                            <Textarea
                                                id="content"
                                                value={data.content}
                                                onChange={(e) => setData('content', e.target.value)}
                                                placeholder="Enter your email content here..."
                                                rows={8}
                                                disabled={!campaign.can_edit}
                                            />
                                        </TabsContent>

                                        <TabsContent value="blocks" className="mt-4">
                                            <BlockBuilder
                                                blocks={blocks}
                                                onChange={setBlocks}
                                                campaignId={campaign.id}
                                            />
                                        </TabsContent>
                                    </Tabs>
                                    {errors.content && <InputError message={errors.content} />}
                                </div>
                                <div className="space-y-4">
                                    <div>
                                        <Label htmlFor="name">Campaign Name</Label>
                                        <Input
                                            id="name"
                                            type="text"
                                            value={data.name}
                                            onChange={(e) => setData('name', e.target.value)}
                                            placeholder="Enter campaign name"
                                            disabled={!campaign.can_edit}
                                        />
                                        {errors.name && <InputError message={errors.name} />}
                                    </div>

                                    <div>
                                        <Label htmlFor="subject">Email Subject</Label>
                                        <Input
                                            id="subject"
                                            type="text"
                                            value={data.subject}
                                            onChange={(e) => setData('subject', e.target.value)}
                                            placeholder="Enter email subject"
                                            disabled={!campaign.can_edit}
                                        />
                                        {errors.subject && <InputError message={errors.subject} />}
                                    </div>


                                    <div>
                                        <Label htmlFor="newsletter_list_id">Newsletter List</Label>
                                        <Select
                                            value={data.newsletter_list_id}
                                            onValueChange={(value) => setData('newsletter_list_id', value)}
                                            disabled={!campaign.can_edit}
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
                                        {errors.newsletter_list_id &&
                                            <InputError message={errors.newsletter_list_id} />}
                                    </div>

                                    <div>
                                        <Label htmlFor="status">Status</Label>
                                        <Select
                                            value={data.status}
                                            onValueChange={(value) => setData('status', value)}
                                            disabled={!campaign.can_edit}
                                        >
                                            <SelectTrigger>
                                                <SelectValue placeholder="Select status" />
                                            </SelectTrigger>
                                            <SelectContent>
                                                <SelectItem value="draft">Draft</SelectItem>
                                                <SelectItem value="scheduled">Scheduled</SelectItem>
                                            </SelectContent>
                                        </Select>
                                        {errors.status && <InputError message={errors.status} />}
                                    </div>

                                    {data.status === 'scheduled' && (
                                        <div>
                                            <Label htmlFor="scheduled_at">Scheduled Date & Time</Label>
                                            <Input
                                                id="scheduled_at"
                                                type="datetime-local"
                                                value={data.scheduled_at}
                                                onChange={(e) => setData('scheduled_at', e.target.value)}
                                                disabled={!campaign.can_edit}
                                            />
                                            {errors.scheduled_at &&
                                                <InputError message={errors.scheduled_at} />}
                                        </div>
                                    )}</div>
                            </div>

                            <div className="flex gap-2">
                                <Button type="submit" disabled={processing || !campaign.can_edit}>
                                    Update Campaign
                                </Button>
                                <EmailPreview 
                                    campaign={campaign}
                                    blocks={contentType === 'blocks' ? blocks : []}
                                    content={contentType === 'simple' ? data.content : ''}
                                />
                                <Button variant="outline" asChild>
                                    <Link href={route('campaigns.show', campaign.id)}>
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
