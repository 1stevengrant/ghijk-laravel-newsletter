import { useState, useCallback } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import FileUpload from '@/components/ui/file-upload';
import {
  Plus,
  Trash2,
  GripVertical,
  Type,
  Image as ImageIcon,
  Quote,
  List,
  Minus,
  ChevronUp,
  ChevronDown
} from 'lucide-react';
import { cn } from '@/lib/utils';
import BlockEditor from './block-editor';

export type BlockType = 'text' | 'image' | 'quote' | 'list' | 'separator';

export interface Block {
  id: string;
  type: BlockType;
  content: string;
  settings?: {
    imageUrl?: string;
    imageAlt?: string;
    imagePath?: string;
    listType?: 'bulleted' | 'numbered';
    quoteAuthor?: string;
  };
}

interface BlockBuilderProps {
  blocks: Block[];
  onChange: (blocks: Block[]) => void;
  className?: string;
  campaignId?: number;
}

const blockTypes = [
  { type: 'text' as const, label: 'Text Block', icon: Type },
  { type: 'image' as const, label: 'Image Block', icon: ImageIcon },
  { type: 'quote' as const, label: 'Quote Block', icon: Quote },
  { type: 'list' as const, label: 'List Block', icon: List },
  { type: 'separator' as const, label: 'Separator', icon: Minus },
];

export default function BlockBuilder({ blocks, onChange, className, campaignId }: BlockBuilderProps) {
  const [selectedBlockId, setSelectedBlockId] = useState<string | null>(null);

  const generateId = () => Math.random().toString(36).substr(2, 9);

  const addBlock = useCallback((type: BlockType) => {
    const newBlock: Block = {
      id: generateId(),
      type,
      content: '',
      settings: {
        listType: 'bulleted',
      },
    };

    onChange([...blocks, newBlock]);
    setSelectedBlockId(newBlock.id);
  }, [blocks, onChange]);

  const updateBlock = useCallback((id: string, updates: Partial<Block>) => {
    onChange(blocks.map(block =>
      block.id === id ? { ...block, ...updates } : block
    ));
  }, [blocks, onChange]);

  const deleteBlock = useCallback((id: string) => {
    onChange(blocks.filter(block => block.id !== id));
    if (selectedBlockId === id) {
      setSelectedBlockId(null);
    }
  }, [blocks, onChange, selectedBlockId]);

  const moveBlock = useCallback((id: string, direction: 'up' | 'down') => {
    const index = blocks.findIndex(block => block.id === id);
    if (index === -1) return;

    const newIndex = direction === 'up' ? index - 1 : index + 1;
    if (newIndex < 0 || newIndex >= blocks.length) return;

    const newBlocks = [...blocks];
    [newBlocks[index], newBlocks[newIndex]] = [newBlocks[newIndex], newBlocks[index]];
    onChange(newBlocks);
  }, [blocks, onChange]);

  const renderBlockContent = (block: Block) => {
    switch (block.type) {
      case 'text':
        return (
          <div className="space-y-2">
            <BlockEditor
              content={block.content}
              onChange={(content) => updateBlock(block.id, { content })}
              placeholder="Enter your text content..."
            />
          </div>
        );

      case 'image':
        return (
          <div className="space-y-4">
            <div>
              <Label>Upload Image</Label>
              <FileUpload
                accept="image/*"
                maxSize={10 * 1024 * 1024} // 10MB
                uploadUrl={campaignId ? `/campaigns/${campaignId}/images/upload` : '/images/upload'}
                onUpload={(response) => updateBlock(block.id, {
                  settings: {
                    ...block.settings,
                    imageUrl: response.url,
                    imagePath: response.path
                  }
                })}
                onError={(error) => alert(error)}
              />
            </div>
            <div>
              <Label htmlFor={`image-alt-${block.id}`}>Alt Text</Label>
              <Input
                id={`image-alt-${block.id}`}
                type="text"
                value={block.settings?.imageAlt || ''}
                onChange={(e) => updateBlock(block.id, {
                  settings: { ...block.settings, imageAlt: e.target.value }
                })}
                placeholder="Enter alt text for accessibility"
              />
            </div>
          </div>
        );

      case 'quote':
        return (
          <div className="space-y-4">
            <div>
              <Label htmlFor={`quote-content-${block.id}`}>Quote Content</Label>
              <Textarea
                id={`quote-content-${block.id}`}
                value={block.content}
                onChange={(e) => updateBlock(block.id, { content: e.target.value })}
                placeholder="Enter your quote text..."
                rows={4}
              />
            </div>
            <div>
              <Label htmlFor={`quote-author-${block.id}`}>Author (Optional)</Label>
              <Input
                id={`quote-author-${block.id}`}
                type="text"
                value={block.settings?.quoteAuthor || ''}
                onChange={(e) => updateBlock(block.id, {
                  settings: { ...block.settings, quoteAuthor: e.target.value }
                })}
                placeholder="Quote author"
              />
            </div>
          </div>
        );

      case 'list':
        return (
          <div className="space-y-4">
            <div>
              <Label htmlFor={`list-type-${block.id}`}>List Type</Label>
              <Select
                value={block.settings?.listType || 'bulleted'}
                onValueChange={(value) => updateBlock(block.id, {
                  settings: { ...block.settings, listType: value as 'bulleted' | 'numbered' }
                })}
              >
                <SelectTrigger>
                  <SelectValue />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="bulleted">Bulleted List</SelectItem>
                  <SelectItem value="numbered">Numbered List</SelectItem>
                </SelectContent>
              </Select>
            </div>
            <div>
              <Label htmlFor={`list-content-${block.id}`}>List Items (one per line)</Label>
              <Textarea
                id={`list-content-${block.id}`}
                value={block.content}
                onChange={(e) => updateBlock(block.id, { content: e.target.value })}
                placeholder="Enter list items, one per line..."
                rows={6}
              />
            </div>
          </div>
        );

      case 'separator':
        return (
          <div className="py-4">
            <div className="border-t border-gray-300"></div>
            <p className="text-sm text-gray-500 mt-2">This will appear as a separator line in your email.</p>
          </div>
        );

      default:
        return null;
    }
  };

  const renderBlockPreview = (block: Block) => {
    switch (block.type) {
      case 'text':
        return (
          <div
            className="prose prose-sm max-w-none"
            dangerouslySetInnerHTML={{ __html: block.content || '<p class="text-gray-400">Enter your text content...</p>' }}
          />
        );

      case 'image': {
        return block.settings?.imageUrl ? (
          <img
            src={block.settings.imageUrl}
            alt={block.settings.imageAlt || 'Image'}
            className="max-w-full h-auto rounded-lg"
            style={{ maxHeight: '200px' }}
          />
        ) : (
          <div className="border-2 border-dashed border-gray-300 rounded-lg p-8 text-center text-gray-400">
            <ImageIcon className="h-12 w-12 mx-auto mb-2" />
            <p>Upload an image to preview</p>
          </div>
        );
      }

      case 'quote':
        return (
          <blockquote className="border-l-4 border-gray-300 pl-4 italic text-gray-700">
            <p>{block.content || 'Enter your quote text...'}</p>
            {block.settings?.quoteAuthor && (
              <cite className="block mt-2 text-sm text-gray-500">— {block.settings.quoteAuthor}</cite>
            )}
          </blockquote>
        );

      case 'list': {
        const items = block.content.split('\n').filter(item => item.trim());
        return block.settings?.listType === 'numbered' ? (
          <ol className="list-decimal list-inside space-y-1">
            {items.map((item, index) => (
              <li key={index}>{item}</li>
            ))}
          </ol>
        ) : (
          <ul className="list-disc list-inside space-y-1">
            {items.map((item, index) => (
              <li key={index}>{item}</li>
            ))}
          </ul>
        );
      }

      case 'separator':
        return <hr className="border-gray-300" />;

      default:
        return null;
    }
  };

  return (
    <div className={cn('space-y-6', className)}>
      {/* Block Type Selector */}
      <Card>
        <CardHeader>
          <CardTitle className="flex items-center gap-2">
            <Plus className="h-5 w-5" />
            Add Content Block
          </CardTitle>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-2 md:grid-cols-5 gap-2">
            {blockTypes.map(({ type, label, icon: Icon }) => (
              <Button
                key={type}
                variant="outline"
                size="sm"
                type="button"
                onClick={() => addBlock(type)}
                className="flex flex-col items-center gap-2 h-auto p-4"
              >
                <Icon className="h-6 w-6" />
                <span className="text-xs">{label}</span>
              </Button>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Content Blocks */}
      {blocks.map((block, index) => (
        <Card key={block.id} className="overflow-hidden">
          <CardHeader className="pb-3">
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-2">
                <GripVertical className="h-4 w-4 text-gray-400" />
                <span className="font-medium capitalize">{block.type} Block</span>
              </div>
              <div className="flex items-center gap-1">
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => moveBlock(block.id, 'up')}
                  disabled={index === 0}
                >
                  <ChevronUp className="h-4 w-4" />
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => moveBlock(block.id, 'down')}
                  disabled={index === blocks.length - 1}
                >
                  <ChevronDown className="h-4 w-4" />
                </Button>
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => deleteBlock(block.id)}
                >
                  <Trash2 className="h-4 w-4" />
                </Button>
              </div>
            </div>
          </CardHeader>
          <CardContent>
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              {/* Block Editor */}
              <div className="space-y-4">
                {renderBlockContent(block)}
              </div>

              {/* Block Preview */}
              <div className="space-y-4">
                <h4 className="font-medium">Preview</h4>
                <div className="border rounded-lg p-4 bg-gray-50 min-h-[100px] prose">
                  {renderBlockPreview(block)}
                </div>
              </div>
            </div>
          </CardContent>
        </Card>
      ))}

      {blocks.length === 0 && (
        <div className="text-center py-12 text-gray-500">
          <Plus className="h-12 w-12 mx-auto mb-4 text-gray-300" />
          <p>No content blocks yet. Add your first block above to get started!</p>
        </div>
      )}
    </div>
  );
}
