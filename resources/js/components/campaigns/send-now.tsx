import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { useForm } from '@inertiajs/react';
import { useState } from 'react';

interface SendNowProps {
    campaign: App.Data.CampaignData;
}

export default function SendNow({ campaign }: SendNowProps) {
    const [open, setOpen] = useState(false);
    const { post, processing } = useForm();

    const handleSend = () => {
        post(route('campaigns.send', campaign.id), {
            onSuccess: () => {
                setOpen(false);
            },
        });
    };

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                <Button 
                    size="sm" 
                    className="bg-green-600 hover:bg-green-700 text-white"
                >
                    Send Now
                </Button>
            </DialogTrigger>
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Send Campaign</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to send "{campaign.name}" to all subscribers? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter>
                    <Button variant="outline" onClick={() => setOpen(false)}>
                        Cancel
                    </Button>
                    <Button 
                        onClick={handleSend} 
                        disabled={processing}
                        className="bg-green-600 hover:bg-green-700 text-white"
                    >
                        {processing ? 'Sending...' : 'Send Campaign'}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    );
}