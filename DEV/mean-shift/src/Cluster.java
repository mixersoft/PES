import java.util.ArrayList;

public class Cluster {
	int Count = 0;
	double[] Centre;
	ArrayList<Item> Items = new ArrayList<Item>();
	double Score = 0.0;

	public Cluster() {
		Centre = null;
		Count = 0;
	}

	public Cluster(double[] cen, int cnt, Item itm) {
		Centre = (double[]) cen.clone();
		Count = cnt;
		Items.add(itm);
	}

	public void filterCluster(double b) {
		if (b == 0.0)
			return;
		ArrayList<Item> strong_items = new ArrayList<Item>();
		for (int i = 0; i < Items.size(); i++) {
			if (Items.get(i).Score >= b) {
				strong_items.add(Items.get(i));
			}
		}

		Items = strong_items;
	}

	public void clusterScore() {
		double mean = 0.0;
		double dev = 0.0;
		for (int i = 0; i < Items.size(); i++) {
			mean = mean + (Double) Items.get(i).RawVector[2];
		}

		mean = mean / Items.size();

		for (int i = 0; i < Items.size(); i++) {
			dev = dev + Math.pow((Double) Items.get(i).RawVector[2] - mean, 2);
		}
		dev = Math.sqrt(dev / Items.size());
		Score = dev;
		// System.out.println("StdDev - " + Double.toString(dev));
	}
}
