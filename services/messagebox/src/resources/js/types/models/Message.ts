import { Attachment } from "./Attachment";

/**
 * Message
 */
export interface Message {
    uuid: string;
    fromName: string;
    toName: string;
    subject: string;
    text: string;
    footer: string;
    createdAt: string;
    expiresAt: string | null;
    attachments: Attachment[];
}
