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
import { Image as ImageIcon, Search, Check, Download, ExternalLink } from 'lucide-react';
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

interface UnsplashPhoto {
  id: string;
  description: string;
  urls: {
    thumb: string;
    small: string;
    regular: string;
    full: string;
    raw: string;
  };
  width: number;
  height: number;
  user: {
    name: string;
    username: string;
    profile_url: string;
  };
  attribution_required: boolean;
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
  
  // Unsplash state
  const [unsplashPhotos, setUnsplashPhotos] = useState<UnsplashPhoto[]>([]);
  const [unsplashLoading, setUnsplashLoading] = useState(false);
  const [unsplashSearchTerm, setUnsplashSearchTerm] = useState('');
  const [unsplashCurrentPage, setUnsplashCurrentPage] = useState(1);
  const [unsplashHasMore, setUnsplashHasMore] = useState(true);
  const [downloadingPhotos, setDownloadingPhotos] = useState<Set<string>>(new Set());

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

  const fetchUnsplashPhotos = async (page = 1, search = '') => {
    if (!search.trim()) {
      setUnsplashPhotos([]);
      return;
    }

    setUnsplashLoading(true);
    try {
      const params = new URLSearchParams({
        query: search,
        page: page.toString(),
        per_page: '20',
      });

      const response = await fetch(`/unsplash/search?${params}`);
      const data = await response.json();

      if (page === 1) {
        setUnsplashPhotos(data.results || []);
      } else {
        setUnsplashPhotos(prev => [...prev, ...(data.results || [])]);
      }

      setUnsplashHasMore(page < (data.total_pages || 0));
      setUnsplashCurrentPage(page);
    } catch (error) {
      console.error('Failed to fetch Unsplash photos:', error);
    } finally {
      setUnsplashLoading(false);
    }
  };

  useEffect(() => {
    const timeoutId = setTimeout(() => {
      if (unsplashSearchTerm.trim()) {
        fetchUnsplashPhotos(1, unsplashSearchTerm);
      } else {
        setUnsplashPhotos([]);
      }
    }, 300);

    return () => clearTimeout(timeoutId);
  }, [unsplashSearchTerm]);

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

  const loadMoreUnsplash = () => {
    if (unsplashHasMore && !unsplashLoading) {
      fetchUnsplashPhotos(unsplashCurrentPage + 1, unsplashSearchTerm);
    }
  };

  const handleUnsplashDownload = async (photo: UnsplashPhoto) => {
    setDownloadingPhotos(prev => new Set([...prev, photo.id]));
    
    try {
      const url = campaignId 
        ? `/campaigns/${campaignId}/unsplash/download`
        : '/unsplash/download';
      
      const response = await fetch(url, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          photo_id: photo.id,
        }),
      });

      const data = await response.json();

      if (data.success) {
        const newImage: ImageData = {
          id: data.id,
          filename: data.filename || `unsplash-${photo.id}.jpg`,
          path: data.path,
          url: data.url,
          original_filename: `unsplash-${photo.id}.jpg`,
          mime_type: 'image/jpeg',
          size: 0,
          width: photo.width,
          height: photo.height,
          alt_text: photo.description,
          created_at: new Date().toISOString(),
        };

        setImages(prev => [newImage, ...prev]);
        onImageSelect(newImage);
        setOpen(false);
      } else {
        alert(data.error || 'Failed to download image');
      }
    } catch (error) {
      console.error('Failed to download Unsplash image:', error);
      alert('Failed to download image');
    } finally {
      setDownloadingPhotos(prev => {
        const newSet = new Set(prev);
        newSet.delete(photo.id);
        return newSet;
      });
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
          <TabsList className="grid w-full grid-cols-3">
            <TabsTrigger value="gallery">Image Gallery</TabsTrigger>
            <TabsTrigger value="unsplash">Unsplash</TabsTrigger>
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
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 p-1">
                {filteredImages.map((image) => (
                  <Card
                    key={image.id}
                    className={cn(
                      "group cursor-pointer hover:shadow-lg transition-all duration-200 relative overflow-hidden border-0 shadow-sm hover:shadow-md",
                      selectedImageId === image.id && "ring-2 ring-blue-500 shadow-lg"
                    )}
                    onClick={() => handleImageSelect(image)}
                  >
                    <CardContent className="p-0">
                      <div className="aspect-square relative overflow-hidden">
                        <img
                          src={image.url}
                          alt={image.alt_text || image.original_filename}
                          className="w-full h-full object-cover transition-transform duration-200 group-hover:scale-105"
                          loading="lazy"
                        />
                        
                        {/* Selection overlay */}
                        {selectedImageId === image.id && (
                          <div className="absolute inset-0 bg-blue-500/20 flex items-center justify-center">
                            <div className="bg-blue-500 rounded-full p-1">
                              <Check className="h-4 w-4 text-white" />
                            </div>
                          </div>
                        )}

                        {/* Hover overlay with image info */}
                        <div className={cn(
                          "absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent transition-opacity duration-200",
                          selectedImageId === image.id ? "opacity-100" : "opacity-0 group-hover:opacity-100"
                        )} />
                        
                        <div className={cn(
                          "absolute bottom-0 left-0 right-0 p-2 text-white transition-opacity duration-200",
                          selectedImageId === image.id ? "opacity-100" : "opacity-0 group-hover:opacity-100"
                        )}>
                          <p className="text-xs font-medium truncate mb-1" title={image.original_filename}>
                            {image.original_filename}
                          </p>
                          <div className="flex items-center justify-between text-xs">
                            <span className="text-white/80">
                              {image.width}×{image.height}
                            </span>
                            {image.alt_text && (
                              <span className="text-white/80 truncate ml-2" title={image.alt_text}>
                                {image.alt_text}
                              </span>
                            )}
                          </div>
                        </div>
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

          <TabsContent value="unsplash" className="space-y-4">
            <div className="flex items-center space-x-2">
              <Search className="h-4 w-4 text-gray-400" />
              <Input
                placeholder="Search Unsplash photos..."
                value={unsplashSearchTerm}
                onChange={(e) => setUnsplashSearchTerm(e.target.value)}
                className="flex-1"
              />
            </div>

            <div className="h-[400px] w-full overflow-y-auto">
              <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-3 p-1">
                {unsplashPhotos.map((photo) => (
                  <Card
                    key={photo.id}
                    className="group cursor-pointer hover:shadow-lg transition-all duration-200 relative overflow-hidden border-0 shadow-sm hover:shadow-md"
                  >
                    <CardContent className="p-0">
                      <div className="aspect-square relative overflow-hidden">
                        <img
                          src={photo.urls.small}
                          alt={photo.description}
                          className="w-full h-full object-cover transition-transform duration-200 group-hover:scale-105"
                          loading="lazy"
                        />
                        <div className="absolute inset-0 bg-gradient-to-t from-black/60 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200" />
                        
                        {/* Download Button */}
                        <div className="absolute top-2 right-2">
                          <Button
                            size="sm"
                            onClick={(e) => {
                              e.stopPropagation();
                              handleUnsplashDownload(photo);
                            }}
                            disabled={downloadingPhotos.has(photo.id)}
                            className="h-8 w-8 p-0 bg-black/50 hover:bg-black/70 border-0 opacity-0 group-hover:opacity-100 transition-opacity duration-200"
                            variant="secondary"
                          >
                            {downloadingPhotos.has(photo.id) ? (
                              <div className="animate-spin rounded-full h-3 w-3 border-b-2 border-white"></div>
                            ) : (
                              <Download className="h-3 w-3 text-white" />
                            )}
                          </Button>
                        </div>

                        {/* Photo Info Overlay */}
                        <div className="absolute bottom-0 left-0 right-0 p-2 text-white opacity-0 group-hover:opacity-100 transition-opacity duration-200">
                          <p className="text-xs font-medium truncate mb-1" title={photo.description}>
                            {photo.description || 'Untitled'}
                          </p>
                          <div className="flex items-center justify-between text-xs">
                            <span className="text-white/80">
                              {photo.width}×{photo.height}
                            </span>
                            <div className="flex items-center gap-1">
                              <span className="text-white/80">by</span>
                              <a
                                href={photo.user.profile_url}
                                target="_blank"
                                rel="noopener noreferrer"
                                className="text-white hover:text-blue-200 flex items-center gap-1 font-medium"
                                onClick={(e) => e.stopPropagation()}
                              >
                                {photo.user.name}
                                <ExternalLink className="h-2.5 w-2.5" />
                              </a>
                            </div>
                          </div>
                        </div>
                      </div>
                    </CardContent>
                  </Card>
                ))}
              </div>

              {unsplashLoading && (
                <div className="text-center py-4">
                  <div className="inline-block animate-spin rounded-full h-4 w-4 border-b-2 border-gray-900"></div>
                </div>
              )}

              {unsplashHasMore && !unsplashLoading && unsplashPhotos.length > 0 && (
                <div className="text-center py-4">
                  <Button variant="outline" onClick={loadMoreUnsplash}>
                    Load More
                  </Button>
                </div>
              )}

              {unsplashPhotos.length === 0 && !unsplashLoading && unsplashSearchTerm && (
                <div className="text-center py-8 text-gray-500">
                  <ImageIcon className="h-12 w-12 mx-auto mb-2 text-gray-300" />
                  <p>No photos found</p>
                </div>
              )}

              {!unsplashSearchTerm && (
                <div className="text-center py-8 text-gray-500">
                  <Search className="h-12 w-12 mx-auto mb-2 text-gray-300" />
                  <p>Search for photos on Unsplash</p>
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
