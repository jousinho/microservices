<template>
  <RouterView />
</template>

<style>
*, *::before, *::after {
  box-sizing: border-box;
  margin: 0;
  padding: 0;
}

body {
  font-family: system-ui, sans-serif;
  background: #0f172a;
  color: #f1f5f9;
  min-height: 100vh;
  display: flex;
  align-items: center;
  justify-content: center;
}

#app {
  width: 100%;
  max-width: 480px;
  padding: 1.5rem;
}

h1 {
  font-size: 1.75rem;
  font-weight: 700;
  text-align: center;
  margin-bottom: 2rem;
  color: #a78bfa;
}

button {
  cursor: pointer;
  border: none;
  border-radius: 0.5rem;
  padding: 0.75rem 1.25rem;
  font-size: 1rem;
  font-weight: 600;
  transition: opacity 0.15s;
}

button:disabled {
  opacity: 0.4;
  cursor: not-allowed;
}

button:not(:disabled):hover {
  opacity: 0.85;
}
</style>
