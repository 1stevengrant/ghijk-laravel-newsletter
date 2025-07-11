declare namespace App.Data {
export type CampaignData = {
id: number;
name: string;
subject: string | null;
content: string | null;
newsletter_list_id: number;
status: string;
scheduled_at: string | null;
scheduled_at_friendly: string | null;
sent_at: string | null;
sent_at_friendly: string | null;
sent_count: number;
opens: number;
clicks: number;
unsubscribes: number;
bounces: number;
open_rate: number;
click_rate: number;
unsubscribe_rate: number;
bounce_rate: number;
can_send: boolean;
can_edit: boolean;
can_delete: boolean;
blocks: Array<any> | null;
newsletter_list: App.Data.NewsletterListData | null;
};
export type NewsletterListData = {
id: number;
name: string;
description: string | null;
from_email: string;
from_name: string;
subscribers: Array<App.Data.NewsletterSubscriberData>;
subscribers_count: number;
};
export type NewsletterSubscriberData = {
id: number;
email: string;
first_name: string | null;
last_name: string | null;
subscribed_at: string | null;
verification_token: string | null;
newsletter_list_id: number | null;
};
}
