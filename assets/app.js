document.addEventListener('DOMContentLoaded',()=>{
  document.querySelectorAll('form[data-validate]').forEach(form=>{
    form.addEventListener('submit',e=>{
      let ok=true;
      form.querySelectorAll('[required]').forEach(el=>{
        if(!el.value.trim()){ok=false;el.style.borderColor='#dc2626'}
        else el.style.borderColor='';
      });
      const email=form.querySelector('input[type=email]');
      if(email && email.value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email.value)){ok=false;email.style.borderColor='#dc2626'}
      if(!ok){e.preventDefault();alert('Please correct the highlighted fields.')}
    });
  });
});
