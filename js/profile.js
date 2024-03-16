
const user = localStorage.getItem('userId');

if (!user) {
    // window.location.href = '/login'
    console.log('No User found')
}