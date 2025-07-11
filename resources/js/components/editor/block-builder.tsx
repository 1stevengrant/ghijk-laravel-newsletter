import { useState, useCallback } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
// import FileUpload from '@/components/ui/file-upload';
import * as Collapsible from '@radix-ui/react-collapsible';
import {
    DndContext,
    closestCenter,
    KeyboardSensor,
    PointerSensor,
    useSensor,
    useSensors,
    DragEndEvent
} from '@dnd-kit/core';
import {
    arrayMove,
    SortableContext,
    sortableKeyboardCoordinates,
    verticalListSortingStrategy
} from '@dnd-kit/sortable';
import {
    useSortable
} from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import {
    Plus,
    Trash2,
    GripVertical,
    Type,
    Image as ImageIcon,
    Quote,
    List,
    Minus,
    ChevronDown,
    ChevronRight
} from 'lucide-react';
import { cn } from '@/lib/utils';
import BlockEditor from './block-editor';
import ImageGallery from '@/components/ui/image-gallery';

export type BlockType = 'text' | 'image' | 'quote' | 'list' | 'separator';

export interface Block {
    id: string;
    type: BlockType;
    content: string;
    settings?: {
        imageId?: number;
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
    { type: 'separator' as const, label: 'Separator', icon: Minus }
];

interface SortableBlockItemProps {
    block: Block;
    index: number;
    isCollapsed: boolean;
    campaignId?: number;
    onUpdate: (id: string, updates: Partial<Block>) => void;
    onDelete: (id: string) => void;
    onToggleCollapse: (id: string) => void;
    renderBlockContent: (block: Block) => React.ReactNode;
    renderBlockPreview: (block: Block) => React.ReactNode;
}

function SortableBlockItem({
                               block,
                               isCollapsed,
                               onDelete,
                               onToggleCollapse,
                               renderBlockContent,
                               renderBlockPreview
                           }: SortableBlockItemProps) {
    const {
        attributes,
        listeners,
        setNodeRef,
        transform,
        transition,
        isDragging
    } = useSortable({ id: block.id });

    const style = {
        transform: CSS.Transform.toString(transform),
        transition
    };

    return (
        <div ref={setNodeRef} style={style} className={cn(isDragging && 'opacity-50')}>
            <Collapsible.Root
                open={!isCollapsed}
                onOpenChange={() => onToggleCollapse(block.id)}
            >
                <Card className="overflow-hidden">
                    <CardHeader>
                        <div className="flex items-center justify-between">
                            <div className="flex items-center gap-2">
                                <Collapsible.Trigger asChild>
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        className="h-6 w-6 p-0"
                                    >
                                        {isCollapsed ? (
                                            <ChevronRight className="h-4 w-4" />
                                        ) : (
                                            <ChevronDown className="h-4 w-4" />
                                        )}
                                    </Button>
                                </Collapsible.Trigger>
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    className="h-6 w-6 p-0 cursor-grab active:cursor-grabbing"
                                    {...attributes}
                                    {...listeners}
                                >
                                    <GripVertical className="h-4 w-4 text-gray-400" />
                                </Button>
                                <span className="font-medium capitalize">{block.type} Block</span>
                            </div>
                            <div className="flex items-center gap-1">
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    onClick={() => onDelete(block.id)}
                                >
                                    <Trash2 className="h-4 w-4" />
                                </Button>
                            </div>
                        </div>
                    </CardHeader>
                    <Collapsible.Content
                        className="data-[state=closed]:animate-collapsible-up data-[state=open]:animate-collapsible-down">
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
                    </Collapsible.Content>
                </Card>
            </Collapsible.Root>
        </div>
    );
}

export default function BlockBuilder({ blocks, onChange, className, campaignId }: BlockBuilderProps) {
    const [selectedBlockId, setSelectedBlockId] = useState<string | null>(null);
    const [collapsedBlocks, setCollapsedBlocks] = useState<Set<string>>(new Set());

    const sensors = useSensors(
        useSensor(PointerSensor),
        useSensor(KeyboardSensor, {
            coordinateGetter: sortableKeyboardCoordinates
        })
    );

    const generateId = () => Math.random().toString(36).substr(2, 9);

    const addBlock = useCallback((type: BlockType) => {
        const newBlock: Block = {
            id: generateId(),
            type,
            content: '',
            settings: {
                listType: 'bulleted'
            }
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

    const toggleBlockCollapse = useCallback((id: string) => {
        setCollapsedBlocks(prev => {
            const newSet = new Set(prev);
            if (newSet.has(id)) {
                newSet.delete(id);
            } else {
                newSet.add(id);
            }
            return newSet;
        });
    }, []);

    const handleDragEnd = useCallback((event: DragEndEvent) => {
        const { active, over } = event;

        if (active.id !== over?.id) {
            const oldIndex = blocks.findIndex(block => block.id === active.id);
            const newIndex = blocks.findIndex(block => block.id === over?.id);

            onChange(arrayMove(blocks, oldIndex, newIndex));
        }
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
                            <Label>Select or Upload Image</Label>
                            <ImageGallery
                                campaignId={campaignId}
                                selectedImageId={block.settings?.imageId}
                                onImageSelect={(image) => updateBlock(block.id, {
                                    settings: {
                                        ...block.settings,
                                        imageId: image.id,
                                        imageUrl: image.url,
                                        imagePath: image.path,
                                        imageAlt: image.alt_text || block.settings?.imageAlt || ''
                                    }
                                })}
                            >
                                <Button variant="outline" className="w-full">
                                    {block.settings?.imageUrl ? 'Change Image' : 'Select Image'}
                                </Button>
                            </ImageGallery>
                        </div>
                        {block.settings?.imageUrl && (
                            <div className="space-y-2">
                                <div className="relative">
                                    <img
                                        src={block.settings.imageUrl}
                                        alt={block.settings.imageAlt || 'Selected image'}
                                        className="max-w-full h-auto rounded-lg border"
                                        style={{ maxHeight: '200px' }}
                                    />
                                </div>
                                <Button
                                    variant="outline"
                                    size="sm"
                                    onClick={() => updateBlock(block.id, {
                                        settings: {
                                            ...block.settings,
                                            imageId: undefined,
                                            imageUrl: undefined,
                                            imagePath: undefined,
                                            imageAlt: ''
                                        }
                                    })}
                                >
                                    Remove Image
                                </Button>
                            </div>
                        )}
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
                        <p className="text-sm text-gray-500 mt-2">This will appear as a separator line in your
                            email.</p>
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
            <DndContext
                sensors={sensors}
                collisionDetection={closestCenter}
                onDragEnd={handleDragEnd}
            >
                <SortableContext items={blocks.map(block => block.id)} strategy={verticalListSortingStrategy}>
                    <div className="space-y-6">
                        {blocks.map((block, index) => (
                            <SortableBlockItem
                                key={block.id}
                                block={block}
                                index={index}
                                isCollapsed={collapsedBlocks.has(block.id)}
                                campaignId={campaignId}
                                onUpdate={updateBlock}
                                onDelete={deleteBlock}
                                onToggleCollapse={toggleBlockCollapse}
                                renderBlockContent={renderBlockContent}
                                renderBlockPreview={renderBlockPreview}
                            />
                        ))}
                    </div>
                </SortableContext>
            </DndContext>

            {blocks.length === 0 && (
                <div className="text-center py-12 text-gray-500">
                    <Plus className="h-12 w-12 mx-auto mb-4 text-gray-300" />
                    <p>No content blocks yet. Add your first block above to get started!</p>
                </div>
            )}
        </div>
    );
}
