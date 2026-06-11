import Swal from 'sweetalert2';
import withReactContent from 'sweetalert2-react-content';

const MySwal = withReactContent(Swal);

export const Toast = MySwal.mixin({
  toast: true,
  position: 'top-end',
  showConfirmButton: false,
  timer: 2500,
  timerProgressBar: false,
  background: 'var(--surface)',
  color: 'var(--text-main)',
  customClass: {
    popup: 'glass-toast',
    title: 'toast-title',
    icon: 'toast-icon'
  },
  showClass: {
    popup: 'animate__animated animate__fadeInRight animate__faster'
  },
  hideClass: {
    popup: 'animate__animated animate__fadeOutRight animate__faster'
  },
  didOpen: (toast) => {
    toast.addEventListener('mouseenter', Swal.stopTimer)
    toast.addEventListener('mouseleave', Swal.resumeTimer)
  }
});
