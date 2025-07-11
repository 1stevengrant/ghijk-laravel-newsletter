import React, { useState, useEffect } from 'react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
    DialogTrigger
} from '@/components/ui/dialog';
// import { ScrollArea } from '@/components/ui/scroll-area';
import FileUpload from '@/components/ui/file-upload';
import { Image as ImageIcon, Search, Check } from 'lucide-react';
import { cn } from '@/lib/utils';

interface ImageData {
  id: number;
  filename: string;
  path: string;
  url: string;
  original_filename: string;
  mime_type: string;
  size: number;
  width: number;
  height: number;
  alt_text: string | null;
  created_at: string;
}

interface ImageGalleryProps {
  onImageSelect: (image: ImageData) => void;
  campaignId?: number;
  selectedImageId?: number;
  children?: React.ReactNode;
}

export default function ImageGallery({ onImageSelect, campaignId, selectedImageId, children }: ImageGalleryProps) {
  const [images, setImages] = useState<ImageData[]>([]);
  const [loading, setLoading] = useState(false);
  const [searchTerm, setSearchTerm] = useState('');
  const [currentPage, setCurrentPage] = useState(1);
  const [hasMore, setHasMore] = useState(true);
  const [open, setOpen] = useState(false);

  const fetchImages = async (page = 1, search = '') => {
    setLoading(true);
    try {
      const params = new URLSearchParams({
        page: page.toString(),
        ...(search && { search }),
      });

      const response = await fetch(`/images?${params}`);
      const data = await response.json();

      if (page === 1) {
        setImages(data.images);
      } else {
        setImages(prev => [...prev, ...data.images]);
      }

      setHasMore(data.has_more);
      setCurrentPage(page);
    } catch (error) {
      console.error('Failed to fetch images:', error);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (open) {
      fetchImages(1, searchTerm);
    }
  }, [open, searchTerm]);

  const handleImageUpload = (response: { path: string; url: string; full_url: string; id?: number; filename?: string; original_filename?: string }) => {
    const newImage: ImageData = {
      id: response.id || 0,
      filename: response.filename || 'unknown',
      path: response.path,
      url: response.url,
      original_filename: response.original_filename || 'unknown',
      mime_type: 'image/jpeg',
      size: 0,
      width: 0,
      height: 0,
      alt_text: null,
      created_at: new Date().toISOString(),
    };

    setImages(prev => [newImage, ...prev]);
    onImageSelect(newImage);
    setOpen(false);
  };

  const handleImageSelect = (image: ImageData) => {
    onImageSelect(image);
    setOpen(false);
  };

  const loadMore = () => {
    if (hasMore && !loading) {
      fetchImages(currentPage + 1, searchTerm);
    }
  };

  const filteredImages = images.filter(image =>
    image.original_filename.toLowerCase().includes(searchTerm.toLowerCase()) ||
    (image.alt_text && image.alt_text.toLowerCase().includes(searchTerm.toLowerCase()))
  );

  return (
    <Dialog open={open} onOpenChange={setOpen}>
      <DialogTrigger asChild>
        {children || (
          <Button variant="outline" className="w-full">
            <ImageIcon className="h-4 w-4 mr-2" />
            Select Image
          </Button>
        )}
      </DialogTrigger>
      <DialogContent className="max-w-4xl max-h-[80vh]">
        <DialogHeader>
          <DialogTitle>Select or Upload Image</DialogTitle>
        </DialogHeader>

          <DialogDescription>

          </DialogDescription>

        <Tabs defaultValue="gallery" className="w-full">
          <TabsList className="grid w-full grid-cols-2">
            <TabsTrigger value="gallery">Image Gallery</TabsTrigger>
            <TabsTrigger value="upload">Upload New</TabsTrigger>
          </TabsList>

          <TabsContent value="gallery" className="space-y-4">
            <div className="flex items-center space-x-2">
              <Search className="h-4 w-4 text-gray-400" />
              <Input
                placeholder="Search images..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="flex-1"
              />
            </div>

            <div className="h-[400px] w-full overflow-y-auto">
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4 p-1">
                {filteredImages.map((image) => (
                  <Card
                    key={image.id}
                    className={cn(
                      "cursor-pointer hover:shadow-md transition-shadow relative !py-0",
                      selectedImageId === image.id && "ring-2 ring-blue-500"
                    )}
                    onClick={() => handleImageSelect(image)}
                  >
                    <CardContent className="p-2">
                      <div className="aspect-square relative overflow-hidden rounded-md">
                        <img
                          src={image.url}
                          alt={image.alt_text || image.original_filename}
                          className="w-full h-full object-cover"
                        />
                        {selectedImageId === image.id && (
                          <div className="absolute inset-0 bg-blue-500 bg-opacity-20 flex items-center justify-center">
                            <Check className="h-6 w-6 text-blue-600" />
                          </div>
                        )}
                      </div>
                      <div className="mt-2">
                        <p className="text-xs text-gray-600 truncate" title={image.original_filename}>
                          {image.original_filename}
                        </p>
                        <p className="text-xs text-gray-400">
                          {image.width}×{image.height}
                        </p>
                      </div>
                    </CardContent>
                  </Card>
                ))}
              </div>

              {loading && (
                <div className="text-center py-4">
                  <div className="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-gray-900"></div>
                </div>
              )}

              {hasMore && !loading && (
                <div className="text-center py-4">
                  <Button variant="outline" onClick={loadMore}>
                    Load More
                  </Button>
                </div>
              )}

              {filteredImages.length === 0 && !loading && (
                <div className="text-center py-8 text-gray-500">
                  <ImageIcon className="h-12 w-12 mx-auto mb-2 text-gray-300" />
                  <p>No images found</p>
                </div>
              )}
            </div>
          </TabsContent>

          <TabsContent value="upload" className="space-y-4">
            <div className="space-y-4">
              <Label className="sr-only">Upload New Image</Label>
              <FileUpload
                accept="image/*"
                maxSize={10 * 1024 * 1024} // 10MB
                uploadUrl={campaignId ? `/campaigns/${campaignId}/images/upload` : '/images/upload'}
                onUpload={handleImageUpload}
                onError={(error) => alert(error)}
              />
            </div>
          </TabsContent>
        </Tabs>
      </DialogContent>
    </Dialog>
  );
}
