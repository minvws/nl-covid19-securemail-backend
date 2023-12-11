import { fakerjs } from '@/utils/testUtils';
import { MessageListItem } from '@/types/models/MessageListItem';

export const generateFakeMessageListItem = (): MessageListItem => ({
    uuid: fakerjs.datatype.uuid(),
    fromName: fakerjs.name.firstName(),
    subject: fakerjs.lorem.sentence(),
    createdAt: fakerjs.date.past().toString(),
    isRead: fakerjs.datatype.boolean(),
    hasAttachments: fakerjs.datatype.boolean(),
});

export const generateFakeMessageListItems = (count: number): MessageListItem[] =>
    Array.from({ length: count }, () => generateFakeMessageListItem());
