import { fakerjs } from '@/utils/testUtils';
import { Message } from '@/types/models/Message';

export const generateFakeMessage = (): Message => ({
    uuid: fakerjs.datatype.uuid(),
    fromName: fakerjs.company.companyName(),
    toName: fakerjs.name.firstName(),
    subject: fakerjs.lorem.sentence(),
    text: fakerjs.lorem.paragraphs(),
    footer: fakerjs.lorem.paragraph(),
    createdAt: fakerjs.date.past().toString(),
    expiresAt: fakerjs.date.future().toString(),
    attachments: [],
});
