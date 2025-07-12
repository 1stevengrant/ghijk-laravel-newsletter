import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { RadioGroup, RadioGroupItem } from '@/components/ui/radio-group';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { toast } from 'sonner';
import { UploadIcon } from 'lucide-react';

interface ImportSubscribersProps {
    lists: App.Data.NewsletterListData[];
}

export default function ImportSubscribers({ lists }: ImportSubscribersProps) {
    const [isOpen, setIsOpen] = useState(false);
    const [file, setFile] = useState<File | null>(null);
    const [importType, setImportType] = useState<'existing' | 'new'>('existing');
    const [selectedListId, setSelectedListId] = useState<string>('');
    const [newListData, setNewListData] = useState({
        name: '',
        description: '',
        from_name: '',
        from_email: '',
    });
    const [isSubmitting, setIsSubmitting] = useState(false);

    const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
        const selectedFile = e.target.files?.[0];
        if (selectedFile) {
            if (selectedFile.type === 'text/csv' || selectedFile.name.endsWith('.csv')) {
                setFile(selectedFile);
            } else {
                toast.error('Please select a CSV file');
                e.target.value = '';
            }
        }
    };

    const handleSubmit = async (e: React.FormEvent) => {
        e.preventDefault();

        if (!file) {
            toast.error('Please select a CSV file');
            return;
        }

        if (importType === 'existing' && !selectedListId) {
            toast.error('Please select a newsletter list');
            return;
        }

        if (importType === 'new' && (!newListData.name || !newListData.from_name || !newListData.from_email)) {
            toast.error('Please fill in all required fields for the new list');
            return;
        }

        setIsSubmitting(true);

        const formData = new FormData();
        formData.append('file', file);
        formData.append('import_type', importType);

        if (importType === 'existing') {
            formData.append('newsletter_list_id', selectedListId);
        } else {
            formData.append('new_list_name', newListData.name);
            formData.append('new_list_description', newListData.description);
            formData.append('new_list_from_name', newListData.from_name);
            formData.append('new_list_from_email', newListData.from_email);
        }

        try {
            const response = await fetch(route('imports.store'), {
                method: 'POST',
                body: formData,
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
            });

            const data = await response.json();

            if (response.ok) {
                setIsOpen(false);
                setFile(null);
                setSelectedListId('');
                setNewListData({ name: '', description: '', from_name: '', from_email: '' });
            } else {
                toast.error(data.message || 'Import failed to start');
            }
        } catch (error) {
            console.log(error)
            toast.error('An error occurred while starting the import');
        } finally {
            setIsSubmitting(false);
        }
    };


    return (
        <Dialog open={isOpen} onOpenChange={setIsOpen}>
            <DialogTrigger asChild>
                <Button variant="outline">
                    <UploadIcon className="h-4 w-4 mr-2" />
                    Import Subscribers
                </Button>
            </DialogTrigger>
            <DialogContent className="sm:max-w-[500px]">
                <DialogHeader>
                    <DialogTitle>Import Subscribers from CSV</DialogTitle>
                </DialogHeader>

                <form onSubmit={handleSubmit} className="space-y-4">
                        <div>
                            <Label htmlFor="csv-file">CSV File</Label>
                            <Input
                                id="csv-file"
                                type="file"
                                accept=".csv"
                                onChange={handleFileChange}
                                required
                            />
                            <p className="text-sm text-gray-500 mt-1">
                                CSV should contain at least an 'email' column. Optional columns: 'first_name', 'last_name'
                            </p>
                        </div>

                        <div>
                            <Label>Import to</Label>
                            <RadioGroup value={importType} onValueChange={(value) => setImportType(value as 'existing' | 'new')}>
                                <div className="flex items-center space-x-2">
                                    <RadioGroupItem value="existing" id="existing" />
                                    <Label htmlFor="existing">Existing List</Label>
                                </div>
                                <div className="flex items-center space-x-2">
                                    <RadioGroupItem value="new" id="new" />
                                    <Label htmlFor="new">New List</Label>
                                </div>
                            </RadioGroup>
                        </div>

                        {importType === 'existing' && (
                            <div>
                                <Label htmlFor="list-select">Select List</Label>
                                <Select value={selectedListId} onValueChange={setSelectedListId}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Choose a list" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {lists.map((list) => (
                                            <SelectItem key={list.id} value={list.id.toString()}>
                                                {list.name} ({list.subscribers_count} subscribers)
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                        )}

                        {importType === 'new' && (
                            <div className="space-y-3">
                                <div>
                                    <Label htmlFor="list-name">List Name</Label>
                                    <Input
                                        id="list-name"
                                        value={newListData.name}
                                        onChange={(e) => setNewListData(prev => ({ ...prev, name: e.target.value }))}
                                        required
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="list-description">Description (optional)</Label>
                                    <Textarea
                                        id="list-description"
                                        value={newListData.description}
                                        onChange={(e) => setNewListData(prev => ({ ...prev, description: e.target.value }))}
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="from-name">From Name</Label>
                                    <Input
                                        id="from-name"
                                        value={newListData.from_name}
                                        onChange={(e) => setNewListData(prev => ({ ...prev, from_name: e.target.value }))}
                                        required
                                    />
                                </div>
                                <div>
                                    <Label htmlFor="from-email">From Email</Label>
                                    <Input
                                        id="from-email"
                                        type="email"
                                        value={newListData.from_email}
                                        onChange={(e) => setNewListData(prev => ({ ...prev, from_email: e.target.value }))}
                                        required
                                    />
                                </div>
                            </div>
                        )}

                        <div className="flex justify-end space-x-2">
                            <Button type="button" variant="outline" onClick={() => setIsOpen(false)}>
                                Cancel
                            </Button>
                            <Button type="submit" disabled={isSubmitting}>
                                {isSubmitting ? 'Starting Import...' : 'Start Import'}
                            </Button>
                        </div>
                    </form>
            </DialogContent>
        </Dialog>
    );
}
