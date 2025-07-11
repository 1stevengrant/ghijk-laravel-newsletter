import { useState, useRef, useCallback } from 'react';
import { Input } from '@/components/ui/input';
import { Upload, Loader2 } from 'lucide-react';
import { cn } from '@/lib/utils';

interface FileUploadProps {
  accept?: string;
  maxSize?: number; // in bytes
  uploadUrl?: string;
  onUpload: (response: { path: string; url: string; full_url: string }) => void;
  onError?: (error: string) => void;
  className?: string;
  children?: React.ReactNode;
}

export default function FileUpload({
  accept = 'image/*',
  maxSize = 10 * 1024 * 1024, // 10MB
  uploadUrl = '/images/upload',
  onUpload,
  onError,
  className,
  children
}: FileUploadProps) {
  const [uploading, setUploading] = useState(false);
  const [dragActive, setDragActive] = useState(false);
  const fileInputRef = useRef<HTMLInputElement>(null);

  const handleFile = useCallback(async (file: File) => {
    if (file.size > maxSize) {
      onError?.(`File size must be less than ${Math.round(maxSize / 1024 / 1024)}MB`);
      return;
    }

    setUploading(true);
    const formData = new FormData();
    formData.append('image', file);

    try {
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
      
      if (!csrfToken) {
        throw new Error('CSRF token not found');
      }

      const response = await fetch(uploadUrl, {
        method: 'POST',
        body: formData,
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
        },
      });

      if (!response.ok) {
        const errorData = await response.json().catch(() => ({}));
        throw new Error(errorData.message || `Upload failed with status ${response.status}`);
      }

      const data = await response.json();
      onUpload(data);
    } catch (error) {
      console.error('Upload error:', error);
      onError?.(error instanceof Error ? error.message : 'Upload failed');
    } finally {
      setUploading(false);
    }
  }, [maxSize, onUpload, onError, uploadUrl]);

  const handleDrag = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true);
    } else if (e.type === 'dragleave') {
      setDragActive(false);
    }
  }, []);

  const handleDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault();
    e.stopPropagation();
    setDragActive(false);

    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFile(e.dataTransfer.files[0]);
    }
  }, [handleFile]);

  const handleInputChange = useCallback((e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files && e.target.files[0]) {
      handleFile(e.target.files[0]);
    }
  }, [handleFile]);

  const openFileDialog = useCallback(() => {
    fileInputRef.current?.click();
  }, []);

  if (children) {
    return (
      <div className={className}>
        <div
          className={cn(
            'relative cursor-pointer',
            dragActive && 'opacity-50'
          )}
          onDragEnter={handleDrag}
          onDragLeave={handleDrag}
          onDragOver={handleDrag}
          onDrop={handleDrop}
          onClick={openFileDialog}
        >
          {children}
          <Input
            ref={fileInputRef}
            type="file"
            accept={accept}
            onChange={handleInputChange}
            className="hidden"
            disabled={uploading}
          />
          {uploading && (
            <div className="absolute inset-0 flex items-center justify-center bg-black bg-opacity-50 rounded">
              <Loader2 className="h-6 w-6 animate-spin text-white" />
            </div>
          )}
        </div>
      </div>
    );
  }

  return (
    <div className={className}>
      <div
        className={cn(
          'border-2 border-dashed border-gray-300 rounded-lg p-6 text-center cursor-pointer transition-colors',
          dragActive && 'border-blue-500 bg-blue-50',
          uploading && 'pointer-events-none opacity-50'
        )}
        onDragEnter={handleDrag}
        onDragLeave={handleDrag}
        onDragOver={handleDrag}
        onDrop={handleDrop}
        onClick={openFileDialog}
      >
        <Input
          ref={fileInputRef}
          type="file"
          accept={accept}
          onChange={handleInputChange}
          className="hidden"
          disabled={uploading}
        />
        
        {uploading ? (
          <div className="flex flex-col items-center">
            <Loader2 className="h-8 w-8 animate-spin text-blue-500 mb-2" />
            <p className="text-gray-600">Uploading...</p>
          </div>
        ) : (
          <div className="flex flex-col items-center">
            <Upload className="h-8 w-8 text-gray-400 mb-2" />
            <p className="text-gray-600 mb-1">
              <span className="font-medium">Click to upload</span> or drag and drop
            </p>
            <p className="text-sm text-gray-500">
              {accept} up to {Math.round(maxSize / 1024 / 1024)}MB
            </p>
          </div>
        )}
      </div>
    </div>
  );
}