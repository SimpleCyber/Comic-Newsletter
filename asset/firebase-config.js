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
 * Save data to a Firestore collection
 * @param {string} collectionName - Name of the collection (e.g. 'users', 'subscribers', 'bookmarks')
 * @param {object} data - Object containing the fields to save
 * @param {string} [docId] - Optional custom document ID
 */
async function saveToFirestore(collectionName, data, docId = null) {
  if (!db) {
    console.error("Firebase Firestore is not initialized. Check your .env configuration.");
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
 * @param {string} collectionName
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
 * @param {object} comic - Comic data { num, title, img, alt }
 * @param {string} [userEmail] - Optional user identifier
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
 * @param {string} email
 */
async function saveNewsletterSubscriber(email) {
  return await saveToFirestore('subscribers', {
    email: email,
    subscribedAt: new Date().toISOString(),
    status: 'active'
  }, email.replace(/[^a-zA-Z0-9]/g, '_'));
}
