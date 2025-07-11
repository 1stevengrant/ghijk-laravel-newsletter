import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import BlockBuilder, { type Block } from '@/components/editor/block-builder';
import { FormEvent, useState } from 'react';

export default function CampaignContent({ campaign }: {
    campaign: App.Data.CampaignData;
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
            title: 'Content',
            href: `/campaigns/${campaign.id}/content`
        }
    ];

    const { data, setData, put, processing, errors } = useForm({
        content: campaign.content || '',
        status: campaign.status,
        scheduled_at: campaign.scheduled_at ? campaign.scheduled_at.slice(0, 16) : '',
    });

    const [blocks, setBlocks] = useState<Block[]>([]);
    const [contentType, setContentType] = useState<'simple' | 'blocks'>('simple');

    const handleSubmit = (e: FormEvent) => {
        e.preventDefault();
        
        // Convert blocks to HTML if using block builder
        if (contentType === 'blocks') {
            const htmlContent = convertBlocksToHtml(blocks);
            setData('content', htmlContent);
        }
        
        put(route('campaigns.content.update', campaign.id));
    };

    const convertBlocksToHtml = (blocks: Block[]): string => {
        return blocks.map(block => {
            switch (block.type) {
                case 'text':
                    return block.content;
                case 'image': {
                    const imageUrl = block.settings?.imageUrl || '';
                    const fullUrl = imageUrl.startsWith('http') ? imageUrl : `${window.location.origin}${imageUrl}`;
                    return `<div style="text-align: center; margin: 20px 0;"><img src="${fullUrl}" alt="${block.settings?.imageAlt || ''}" style="max-width: 100%; height: auto; border-radius: 8px;" /></div>`;
                }
                case 'quote': {
                    const author = block.settings?.quoteAuthor ? `<cite>— ${block.settings.quoteAuthor}</cite>` : '';
                    return `<blockquote style="border-left: 4px solid #e5e7eb; padding-left: 1rem; font-style: italic; color: #6b7280;"><p>${block.content}</p>${author}</blockquote>`;
                }
                case 'list': {
                    const items = block.content.split('\n').filter(item => item.trim());
                    const listItems = items.map(item => `<li>${item}</li>`).join('');
                    return block.settings?.listType === 'numbered' 
                        ? `<ol>${listItems}</ol>`
                        : `<ul>${listItems}</ul>`;
                }
                case 'separator':
                    return '<hr style="border: none; border-top: 1px solid #e5e7eb; margin: 1rem 0;" />';
                default:
                    return '';
            }
        }).join('\n');
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`${campaign.name} - Content`} />
            <div className="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
                <Card>
                    <CardHeader>
                        <CardTitle>Create Campaign Content - Step 2</CardTitle>
                        <CardDescription>
                            Add content to your campaign: <strong>{campaign.name}</strong>
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-4">
                            <div>
                                <Label htmlFor="content">Email Content</Label>
                                <Tabs value={contentType} onValueChange={(value) => setContentType(value as 'simple' | 'blocks')}>
                                    <TabsList className="grid w-full grid-cols-2">
                                        <TabsTrigger value="simple">Simple Editor</TabsTrigger>
                                        <TabsTrigger value="blocks">Block Builder</TabsTrigger>
                                    </TabsList>
                                    
                                    <TabsContent value="simple" className="mt-4">
                                        <Textarea
                                            id="content"
                                            value={data.content}
                                            onChange={(e) => setData('content', e.target.value)}
                                            placeholder="Enter your email content here..."
                                            rows={12}
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
                                {errors.content && <p className="text-red-500 text-sm">{errors.content}</p>}
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
                                    Save Campaign
                                </Button>
                                <Button variant="outline" asChild>
                                    <Link href={route('campaigns.show', campaign.id)}>
                                        Preview
                                    </Link>
                                </Button>
                                <Button variant="ghost" asChild>
                                    <Link href={route('campaigns.index')}>
                                        Back to Campaigns
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