import { Ionicons } from '@expo/vector-icons';
import React, { useState } from 'react';
import {
    Dimensions,
    FlatList,
    Image,
    StyleSheet,
    Text,
    TextInput,
    TouchableOpacity,
    useColorScheme,
    View,
} from 'react-native';

const { width } = Dimensions.get('window');
const CARD_WIDTH = (width - 44) / 2; // Dynamically balances two columns with standard margins

interface CategoryItem {
  id: string;
  name: string;
  icon: string;
  bgColor: string;
  iconColor: string;
}

interface CityItem {
  id: string;
  name: string;
  image: string;
}

interface PlaceItem {
  id: string;
  name: string;
  distance: string;
  image: string;
}

// Category lists mapping background/icon colors
const categories: CategoryItem[] = [
  { id: '1', name: 'Things to do', icon: 'ribbon', bgColor: '#FFF2E6', iconColor: '#FF8C00' },
  { id: '2', name: 'Transport', icon: 'bus', bgColor: '#E6F0FA', iconColor: '#3B82F6' },
  { id: '3', name: 'Car rentals', icon: 'car', bgColor: '#E6F7ED', iconColor: '#10B981' },
  { id: '4', name: 'Hotels', icon: 'business', bgColor: '#FFF9E6', iconColor: '#FBBF24' },
  { id: '5', name: 'eSIM', icon: 'wifi', bgColor: '#E6F4F8', iconColor: '#06B6D4' },
  { id: '6', name: 'All', icon: 'apps', bgColor: '#FFEBEA', iconColor: '#EF4444' },
];

const cities: CityItem[] = [
  { id: '1', name: 'Cebu City', image: 'https://picsum.photos/id/1043/100/100' },
  { id: '2', name: 'Bangkok', image: 'https://picsum.photos/id/1035/100/100' },
  { id: '3', name: 'Beijing', image: 'https://picsum.photos/id/1024/100/100' },
];

const recommendedPlaces: PlaceItem[] = [
  { id: '1', name: 'Cebu Ocean Park', distance: '9.3 km from you', image: 'https://picsum.photos/id/1015/400/300' },
  { id: '2', name: 'Carmen Safari', distance: '45 km from you', image: 'https://picsum.photos/id/1025/400/300' },
];

const nearbyPlaces: PlaceItem[] = [
  { id: '2', name: 'Carmen Safari', distance: '45 km from you', image: 'https://picsum.photos/id/1025/400/300' },
];

export default function HomeScreen() {
  const colorScheme = useColorScheme();
  const isDark = colorScheme === 'dark';

  const [searchQuery, setSearchQuery] = useState('');
  const [activeTab, setActiveTab] = useState<'recommended' | 'nearby'>('recommended');

  const activeData = activeTab === 'recommended' ? recommendedPlaces : nearbyPlaces;

  // Dynamic colors mapping based on system device theme
  const theme = {
    bg: isDark ? '#111114' : '#FFFFFF',
    cardBg: isDark ? '#1E2029' : '#F5F5F5',
    border: isDark ? '#2A2D38' : '#E5E5E5',
    textMain: isDark ? '#FFFFFF' : '#111111',
    textSub: isDark ? '#9CA3AF' : '#666666',
    categoryText: isDark ? '#E5E7EB' : '#444444',
    tabInactive: isDark ? '#6B7280' : '#888888',
    accent: '#FF5A1F', // Signature Vivid Orange
  };

  // Render City Pill Header Components
  const renderCityChip = ({ item }: { item: CityItem }) => (
    <TouchableOpacity style={[styles.cityChip, { backgroundColor: theme.cardBg }]}>
      <Image source={{ uri: item.image }} style={styles.cityChipImage} />
      <Text style={[styles.cityChipText, { color: theme.textMain }]}>item.name</Text>
    </TouchableOpacity>
  );

  // Bottom content card item renderer
  const renderPlaceCard = ({ item }: { item: PlaceItem }) => (
    <TouchableOpacity style={[styles.placeCard, { backgroundColor: theme.cardBg }]}>
      <Image source={{ uri: item.image }} style={styles.placeImage} />
      <View style={styles.cardOverlay} />
      <View style={styles.cardTextContainer}>
        <View style={styles.distanceBadge}>
          <Ionicons name="location-sharp" size={10} color="#FFFFFF" style={{ marginRight: 2 }} />
          <Text style={styles.distanceText} numberOfLines={1}>{item.distance}</Text>
        </View>
        <Text style={styles.placeName} numberOfLines={1}>{item.name}</Text>
      </View>
    </TouchableOpacity>
  );

  // Grouped header items (Search Bar + Category Menu Grid + Destination Horizontal Bar)
  const ListHeader = () => (
    <View style={styles.headerContainer}>
      {/* Search Header Strip */}
      <View style={styles.searchBarRow}>
        <View style={[styles.searchBarWrapper, { backgroundColor: theme.cardBg, borderColor: theme.border }]}>
          <Ionicons name="search-outline" size={18} color={theme.textSub} style={styles.searchIcon} />
          <TextInput
            style={[styles.searchInput, { color: theme.textMain }]}
            placeholder="Search Place"
            placeholderTextColor={theme.textSub}
            value={searchQuery}
            onChangeText={setSearchQuery}
          />
        </View>
        <TouchableOpacity style={styles.iconButton}>
          <Ionicons name="cart-outline" size={24} color={theme.textMain} />
        </TouchableOpacity>
        <TouchableOpacity style={styles.iconButton}>
          <Ionicons name="notifications-outline" size={24} color={theme.textMain} />
        </TouchableOpacity>
      </View>

      {/* 2x3 Fixed Categories Grid System */}
      <View style={styles.categoriesGrid}>
        {categories.map((category) => (
          <TouchableOpacity key={category.id} style={styles.categoryItem}>
            <View style={[styles.categoryIconBg, { backgroundColor: category.bgColor }]}>
              <Ionicons name={category.icon as any} size={24} color={category.iconColor} />
            </View>
            <Text style={[styles.categoryName, { color: theme.categoryText }]} numberOfLines={1}>
              {category.name}
            </Text>
          </TouchableOpacity>
        ))}
      </View>

      {/* Where to next row sections */}
      <View style={styles.rowHeader}>
        <Text style={[styles.sectionTitle, { color: theme.textMain }]}>Where to next?</Text>
        <TouchableOpacity>
          <Text style={[styles.seeMore, { color: theme.textSub }]}>See more</Text>
        </TouchableOpacity>
      </View>

      <FlatList
        data={cities}
        renderItem={renderCityChip}
        keyExtractor={(item) => item.id}
        horizontal
        showsHorizontalScrollIndicator={false}
        contentContainerStyle={styles.citiesListContainer}
      />

      {/* Filter Segment Tabs Bar Switch Toggle Layout */}
      <View style={[styles.tabBarContainer, { borderBottomColor: theme.border }]}>
        <TouchableOpacity 
          onPress={() => setActiveTab('recommended')}
          style={[
            styles.tabButton, 
            activeTab === 'recommended' && { borderBottomColor: theme.accent }
          ]}
        >
          <Text style={[
            styles.tabButtonText, 
            { color: activeTab === 'recommended' ? theme.accent : theme.tabInactive }
          ]}>
            Recommended
          </Text>
        </TouchableOpacity>

        <TouchableOpacity 
          onPress={() => setActiveTab('nearby')}
          style={[
            styles.tabButton, 
            activeTab === 'nearby' && { borderBottomColor: theme.accent }
          ]}
        >
          <Text style={[
            styles.tabButtonText, 
            { color: activeTab === 'nearby' ? theme.accent : theme.tabInactive }
          ]}>
            Nearby
          </Text>
        </TouchableOpacity>
      </View>
    </View>
  );

  return (
    <FlatList
      style={[styles.container, { backgroundColor: theme.bg }]}
      data={activeData}
      renderItem={renderPlaceCard}
      keyExtractor={(item) => item.id}
      ListHeaderComponent={ListHeader}
      numColumns={2}
      columnWrapperStyle={styles.cardRowWrapper}
      showsVerticalScrollIndicator={false}
      contentContainerStyle={styles.scrollContent}
    />
  );
}

const styles = StyleSheet.create({
  container: {
    flex: 1,
  },
  scrollContent: {
    paddingBottom: 32,
  },
  headerContainer: {
    paddingHorizontal: 16,
    paddingTop: 48,
  },

  // Search Strip styling layouts
  searchBarRow: {
    flexDirection: 'row',
    alignItems: 'center',
    marginBottom: 20,
  },
  searchBarWrapper: {
    flex: 1,
    flexDirection: 'row',
    alignItems: 'center',
    borderRadius: 24,
    borderWidth: 1,
    paddingHorizontal: 14,
    height: 42,
  },
  searchIcon: {
    marginRight: 6,
  },
  searchInput: {
    flex: 1,
    fontSize: 15,
    padding: 0,
  },
  iconButton: {
    marginLeft: 12,
    padding: 2,
  },

  // 2x3 Categories Grid System
  categoriesGrid: {
    flexDirection: 'row',
    flexWrap: 'wrap',
    justifyContent: 'space-between',
    marginBottom: 16,
  },
  categoryItem: {
    width: '16%', // Distributes cleanly evenly into 6 items across rows
    alignItems: 'center',
    marginBottom: 14,
  },
  categoryIconBg: {
    width: 46,
    height: 46,
    borderRadius: 12,
    justifyContent: 'center',
    alignItems: 'center',
    marginBottom: 6,
  },
  categoryName: {
    fontSize: 10,
    fontWeight: '500',
    textAlign: 'center',
  },

  // Typography Label Titles
  rowHeader: {
    flexDirection: 'row',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginTop: 8,
    marginBottom: 12,
  },
  sectionTitle: {
    fontSize: 18,
    fontWeight: '700',
    letterSpacing: -0.3,
  },
  seeMore: {
    fontSize: 13,
    fontWeight: '500',
  },

  // Horizontally Scrollable Cities layout
  citiesListContainer: {
    paddingBottom: 16,
  },
  cityChip: {
    flexDirection: 'row',
    alignItems: 'center',
    paddingVertical: 6,
    paddingLeft: 6,
    paddingRight: 14,
    borderRadius: 24,
    marginRight: 10,
  },
  cityChipImage: {
    width: 32,
    height: 32,
    borderRadius: 16,
    marginRight: 8,
  },
  cityChipText: {
    fontSize: 13,
    fontWeight: '600',
  },

  // Tab Menu Navigation Switch Bar styles
  tabBarContainer: {
    flexDirection: 'row',
    borderBottomWidth: 1,
    marginTop: 12,
    marginBottom: 16,
  },
  tabButton: {
    paddingBottom: 8,
    marginRight: 24,
    borderBottomWidth: 2,
    borderBottomColor: 'transparent',
  },
  tabButtonText: {
    fontSize: 16,
    fontWeight: '700',
  },

  // 2-Column Grid Layout Cards matching lower container UI
  cardRowWrapper: {
    justifyContent: 'space-between',
    paddingHorizontal: 16,
    marginBottom: 12,
  },
  placeCard: {
    width: CARD_WIDTH,
    height: CARD_WIDTH * 1.25,
    borderRadius: 12,
    overflow: 'hidden',
  },
  placeImage: {
    width: '100%',
    height: '100%',
    resizeMode: 'cover',
  },
  cardOverlay: {
    ...StyleSheet.absoluteFillObject,
    backgroundColor: 'rgba(0,0,0,0.25)', // Smooth ambient darkening tint for legibility
  },
  cardTextContainer: {
    position: 'absolute',
    bottom: 12,
    left: 12,
    right: 12,
  },
  distanceBadge: {
    flexDirection: 'row',
    alignItems: 'center',
    backgroundColor: 'rgba(0,0,0,0.4)',
    alignSelf: 'flex-start',
    paddingHorizontal: 6,
    paddingVertical: 3,
    borderRadius: 4,
    marginBottom: 4,
  },
  distanceText: {
    color: '#FFFFFF',
    fontSize: 10,
    fontWeight: '500',
  },
  placeName: {
    color: '#FFFFFF',
    fontSize: 14,
    fontWeight: '700',
  },
});