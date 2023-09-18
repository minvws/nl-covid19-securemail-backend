import axios from 'axios';
import { sessionResponseInterceptor, sessionErrorInterceptor } from '@/interceptors/sessionInterceptor';

const instance = axios.create({
    headers: {
        'X-Requested-With': 'XMLHttpRequest',
    },
});

instance.interceptors.response.use(sessionResponseInterceptor, sessionErrorInterceptor);

export default instance;
