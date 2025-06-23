console.log('=== Bootstrap.js loading ===');

try {
    console.log('1. Importing axios...');
    import('axios').then(axios => {
        window.axios = axios.default;
        window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
        console.log('2. Axios loaded successfully');
    }).catch(error => {
        console.error('Failed to load axios:', error);
    });
} catch (error) {
    console.error('Error importing axios:', error);
}

try {
    console.log('3. Importing Echo and Pusher...');
    Promise.all([
        import('laravel-echo'),
        import('pusher-js')
    ]).then(([EchoModule, PusherModule]) => {
        console.log('4. Echo and Pusher modules loaded');
        
        const Echo = EchoModule.default;
        const Pusher = PusherModule.default;
        
        window.Pusher = Pusher;
        
        // Hardcoded values for testing
        const echoConfig = {
            broadcaster: 'reverb',
            key: 'mzm0mamkrxpsxp8qssar',
            wsHost: 'localhost',
            wsPort: 8080,
            wssPort: 8080,
            forceTLS: false,
            enabledTransports: ['ws', 'wss'],
        };

        console.log('5. Echo configuration:', echoConfig);
        
        try {
            window.Echo = new Echo(echoConfig);
            console.log('6. Laravel Echo initialized successfully!');
        } catch (error) {
            console.error('6. Failed to initialize Laravel Echo:', error);
        }
    }).catch(error => {
        console.error('Failed to load Echo/Pusher modules:', error);
    });
} catch (error) {
    console.error('Error importing Echo/Pusher:', error);
}

console.log('=== Bootstrap.js loaded ===');
