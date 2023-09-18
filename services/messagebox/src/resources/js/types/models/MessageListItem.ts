/**
 * Message List Item
 */
export interface MessageListItem {
    uuid: string;
    fromName: string;
    subject: string;
    createdAt: string;
    isRead: boolean;
    hasAttachments: boolean;
}
