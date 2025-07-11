import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { 
  Sheet, 
  SheetContent, 
  SheetHeader, 
  SheetTitle, 
  SheetTrigger 
} from '@/components/ui/sheet';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Eye, Monitor, Smartphone, Tablet } from 'lucide-react';
import { convertBlocksToHtml } from '@/utils/block-utils';
import { type Block } from '@/components/editor/block-builder';

interface EmailPreviewProps {
  campaign: App.Data.CampaignData;
  blocks?: Block[];
  content?: string;
  trigger?: React.ReactNode;
}

export default function EmailPreview({ 
  campaign, 
  blocks = [], 
  content = '',
  trigger 
}: EmailPreviewProps) {
  const [viewMode, setViewMode] = useState<'desktop' | 'tablet' | 'mobile'>('desktop');
  
  // Generate the email content
  const emailContent = blocks.length > 0 
    ? convertBlocksToHtml(blocks) 
    : content || campaign.content || '';
  
  const generateFullEmailHTML = () => {
    return `
      <!DOCTYPE html>
      <html lang="en">
      <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>${campaign.name}</title>
        <style>
          body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
          }
          h1, h2, h3 { color: #2d3748; }
          a { color: #3182ce; text-decoration: none; }
          a:hover { text-decoration: underline; }
          .email-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e2e8f0;
          }
          .email-footer {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 14px;
            color: #718096;
          }
          blockquote {
            border-left: 4px solid #e2e8f0;
            padding-left: 1rem;
            margin: 1rem 0;
            font-style: italic;
            color: #4a5568;
          }
          ul, ol {
            padding-left: 1.5rem;
          }
          hr {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 1.5rem 0;
          }
          img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
          }
          @media (max-width: 600px) {
            body {
              padding: 10px;
            }
            .email-header {
              margin-bottom: 20px;
            }
          }
        </style>
      </head>
      <body>
        <div class="email-header">
          <h1>${campaign.name}</h1>
          <p style="color: #718096; margin: 0;">From: ${campaign.newsletter_list?.name || 'Your Newsletter'}</p>
        </div>
        
        <div class="email-content">
          ${emailContent}
        </div>
        
        <div class="email-footer">
          <p>© ${new Date().getFullYear()} ${campaign.newsletter_list?.name || 'Your Newsletter'}. All rights reserved.</p>
          <p style="margin-top: 10px;">
            <a href="#" style="color: #718096; font-size: 12px;">Unsubscribe</a> | 
            <a href="#" style="color: #718096; font-size: 12px;">View in browser</a>
          </p>
        </div>
      </body>
      </html>
    `;
  };
  
  const getViewportClass = () => {
    switch (viewMode) {
      case 'mobile':
        return 'w-[375px] h-[667px]';
      case 'tablet':
        return 'w-[768px] h-[600px]';
      default:
        return 'w-full h-[600px]';
    }
  };
  
  return (
    <Sheet>
      <SheetTrigger asChild>
        {trigger || (
          <Button variant="outline">
            <Eye className="h-4 w-4 mr-2" />
            Preview Email
          </Button>
        )}
      </SheetTrigger>
      <SheetContent className="w-full sm:max-w-[90vw] lg:max-w-[95vw]" side="right">
        <SheetHeader>
          <SheetTitle>Email Preview: {campaign.name}</SheetTitle>
        </SheetHeader>
        
        <div className="flex flex-col h-full">
          <Tabs value={viewMode} onValueChange={(value) => setViewMode(value as typeof viewMode)} className="w-full">
            <TabsList className="grid w-full grid-cols-3">
              <TabsTrigger value="desktop" className="flex items-center gap-2">
                <Monitor className="h-4 w-4" />
                Desktop
              </TabsTrigger>
              <TabsTrigger value="tablet" className="flex items-center gap-2">
                <Tablet className="h-4 w-4" />
                Tablet
              </TabsTrigger>
              <TabsTrigger value="mobile" className="flex items-center gap-2">
                <Smartphone className="h-4 w-4" />
                Mobile
              </TabsTrigger>
            </TabsList>
            
            <TabsContent value={viewMode} className="mt-4 flex-1">
              <div className="flex justify-center items-start h-full overflow-hidden">
                <div className={`${getViewportClass()} border rounded-lg overflow-hidden bg-white shadow-lg`}>
                  <iframe
                    srcDoc={generateFullEmailHTML()}
                    className="w-full h-full border-0"
                    title="Email Preview"
                  />
                </div>
              </div>
            </TabsContent>
          </Tabs>
        </div>
      </SheetContent>
    </Sheet>
  );
}