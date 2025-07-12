declare namespace App.Data {
export type BlockData = {
id: string;
type: string;
content: string | null;
settings: App.Data.BlockSettingsData | null;
};
export type BlockSettingsData = {
imageId: number | null;
imageUrl: string | null;
imageAlt: string | null;
imagePath: string | null;
listType: string | null;
quoteAuthor: string | null;
};
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
blocks: Array<App.Data.BlockData> | null;
newsletter_list: App.Data.NewsletterListData | null;
};
export type ImportData = {
id: number;
filename: string;
original_filename: string;
status: string;
newsletter_list_id: number | null;
new_list_data: Array<App.Data.NewListData> | null;
total_rows: number;
processed_rows: number;
successful_rows: number;
failed_rows: number;
errors: Array<App.Data.ImportErrorData> | null;
started_at: string | null;
completed_at: string | null;
progress_percentage: number;
newsletter_list: App.Data.NewsletterListData | null;
};
export type ImportErrorData = {
row: number;
message: string;
email: string | null;
};
export type NewListData = {
name: string;
description: string | null;
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
unsubscribed_at: string | null;
verification_token: string | null;
newsletter_list_id: number | null;
status: string | null;
};
}
