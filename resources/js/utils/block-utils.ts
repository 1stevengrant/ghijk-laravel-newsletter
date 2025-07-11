import { type Block } from '@/components/editor/block-builder';

export const convertBlocksToHtml = (blocks: Block[]): string => {
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

export const initializeBlocksFromCampaign = (campaign: App.Data.CampaignData): Block[] => {
    return (campaign.blocks && Array.isArray(campaign.blocks)) ? campaign.blocks : [];
};

export const shouldUseBlocksMode = (campaign: App.Data.CampaignData): boolean => {
    return Boolean(campaign.blocks && Array.isArray(campaign.blocks) && campaign.blocks.length > 0);
};