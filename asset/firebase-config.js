// Firebase Client Initialization
// Firebase configuration is dynamically populated from .env via api/firebase-config.php
const firebaseConfig = window.firebaseConfig || {};

// Initialize Firebase if not already initialized
if (typeof firebase !== 'undefined' && !firebase.apps.length && firebaseConfig.apiKey) {
  firebase.initializeApp(firebaseConfig);
}

// Global references
const db = (typeof firebase !== 'undefined' && firebase.apps.length) ? firebase.firestore() : null;
const auth = (typeof firebase !== 'undefined' && firebase.apps.length) ? firebase.auth() : null;

/**
 * Sign in using Google Auth Popup and bridge to PHP session
 * @param {string} csrfToken
 */
async function signInWithGoogle(csrfToken) {
  if (!auth) {
    throw new Error("Firebase Auth is not initialized. Please verify FIREBASE_* settings in .env");
  }

  const provider = new firebase.auth.GoogleAuthProvider();
  provider.addScope('profile');
  provider.addScope('email');

  const result = await auth.signInWithPopup(provider);
  const user = result.user;
  const idToken = await user.getIdToken();

  const response = await fetch('/api/firebase-auth.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json'
    },
    body: JSON.stringify({
      uid: user.uid,
      email: user.email,
      name: user.displayName || '',
      photoURL: user.photoURL || '',
      provider: 'google.com',
      idToken: idToken,
      csrf_token: csrfToken
    })
  });

  return await response.json();
}

/**
 * Sign in using Firebase Email & Password
 * @param {string} email
 * @param {string} password
 * @param {string} csrfToken
 */
async function signInWithFirebaseEmail(email, password, csrfToken) {
  if (!auth) throw new Error("Firebase Auth is not initialized");

  const result = await auth.signInWithEmailAndPassword(email, password);
  const user = result.user;
  const idToken = await user.getIdToken();

  const response = await fetch('/api/firebase-auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      uid: user.uid,
      email: user.email,
      name: user.displayName || email.split('@')[0],
      provider: 'password',
      idToken: idToken,
      csrf_token: csrfToken
    })
  });

  return await response.json();
}

/**
 * Sign up using Firebase Email & Password
 * @param {string} email
 * @param {string} password
 * @param {string} fullName
 * @param {string} csrfToken
 */
async function signUpWithFirebaseEmail(email, password, fullName, csrfToken) {
  if (!auth) throw new Error("Firebase Auth is not initialized");

  const result = await auth.createUserWithEmailAndPassword(email, password);
  const user = result.user;

  if (fullName && user.updateProfile) {
    await user.updateProfile({ displayName: fullName });
  }

  const idToken = await user.getIdToken();

  const response = await fetch('/api/firebase-auth.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      uid: user.uid,
      email: user.email,
      name: fullName || email.split('@')[0],
      provider: 'password',
      idToken: idToken,
      csrf_token: csrfToken
    })
  });

  return await response.json();
}

/**
 * Save data to a Firestore collection
 */
async function saveToFirestore(collectionName, data, docId = null) {
  if (!db) {
    console.error("Firebase Firestore is not initialized.");
    return { success: false, error: "Firestore not initialized" };
  }

  try {
    const dataWithTimestamp = {
      ...data,
      savedAt: firebase.firestore.FieldValue.serverTimestamp()
    };

    if (docId) {
      await db.collection(collectionName).doc(docId).set(dataWithTimestamp, { merge: true });
      return { success: true, id: docId };
    } else {
      const docRef = await db.collection(collectionName).add(dataWithTimestamp);
      return { success: true, id: docRef.id };
    }
  } catch (error) {
    console.error(`Error saving to ${collectionName}:`, error);
    return { success: false, error };
  }
}

/**
 * Fetch documents from a Firestore collection
 */
async function getFromFirestore(collectionName) {
  if (!db) return [];
  try {
    const snapshot = await db.collection(collectionName).get();
    return snapshot.docs.map(doc => ({ id: doc.id, ...doc.data() }));
  } catch (error) {
    console.error(`Error fetching from ${collectionName}:`, error);
    return [];
  }
}

/**
 * Example: Bookmark a comic in Firestore
 */
async function bookmarkComic(comic, userEmail = 'guest') {
  return await saveToFirestore('bookmarked_comics', {
    comicId: comic.num,
    title: comic.safe_title || comic.title,
    img: comic.img,
    alt: comic.alt,
    userEmail: userEmail
  }, `${userEmail}_comic_${comic.num}`);
}

/**
 * Example: Save newsletter subscriber directly to Firestore
 */
async function saveNewsletterSubscriber(email) {
  return await saveToFirestore('subscribers', {
    email: email,
    subscribedAt: new Date().toISOString(),
    status: 'active'
  }, email.replace(/[^a-zA-Z0-9]/g, '_'));
}
