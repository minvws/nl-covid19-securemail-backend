import instance from './defaults';

export const getMessageByUuid = (messageUuid: string) =>
    instance.get(`/api/v1/messages/${messageUuid}`).then(res => res.data);

export const getMessageList = () => instance.get('/api/v1/messages').then(res => res.data);
